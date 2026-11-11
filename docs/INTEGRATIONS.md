# NoniKos — Integrations

**Status:** adapters implemented, **not live**. No provider credentials exist in this environment, so nothing here has been exercised against a real provider. Do not claim otherwise in UI, docs, or reports.

## Design

Both integrations sit behind interfaces bound in `app/Providers/AppServiceProvider.php`:

| Interface | Default binding | Live when |
|---|---|---|
| `App\Services\Integrations\PaymentGateway` | `SandboxPaymentGateway` | `PAYMENT_GATEWAY_KEY` + `PAYMENT_GATEWAY_BASE_URL` set |
| `App\Services\Integrations\WhatsAppSender` | `WhatsAppCloudSender` | `WHATSAPP_TOKEN` + `WHATSAPP_PHONE_NUMBER_ID` set |

Swapping a provider means writing one class that implements the interface and rebinding it. No controller changes.

## Environment variables

```dotenv
# Payment gateway (generic hosted-link provider)
PAYMENT_GATEWAY_KEY=
PAYMENT_GATEWAY_BASE_URL=
PAYMENT_GATEWAY_WEBHOOK_SECRET=

# WhatsApp Cloud API
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
```

All are read through `config/services.php`. Never commit values.

## Payment gateway

### Outbound
`SandboxPaymentGateway::createLink()` creates a `payment_links` row with a 40-char token and returns a URL on this application (`/pay/{token}`). When live config exists it also POSTs `{reference, amount, currency: IDR, description, callback_url}` to `{base_url}/payment-links` and stores `external_id` and `payment_url`. A provider failure marks the link `failed` and throws; the UI shows an error.

### Inbound webhook
- Endpoint: `POST /webhooks/payments/{provider}` (`webhooks.payments`), throttled `120/min`. CSRF is exempted for this path in `VerifyCsrfToken` because the request is authenticated by the HMAC signature instead.
- Verification: `X-Signature` header must equal `hash_hmac('sha256', rawBody, PAYMENT_GATEWAY_WEBHOOK_SECRET)`.
- Without a configured secret every webhook is rejected with 400. This is deliberate: an unauthenticated payment endpoint must fail closed.
- Idempotency: unique `(provider, event_id)` in `payment_webhook_events`; duplicates return 200 and do nothing.
- Validation: currency must be `IDR`; amount must equal the link amount; status must be `paid` before any payment is recorded.
- On success the link is marked `paid` and a `payment` row is created via `PaymentService::confirmOnline()` inside a transaction. Failures are stored on the event row (`status`, `error`) and logged.

### Public payment page
`GET /pay/{token}` renders the invoice summary and explicitly labels sandbox mode. It never marks anything paid; only a verified webhook does.

## WhatsApp

`WhatsAppCloudSender` posts a plain text message to `https://graph.facebook.com/v20.0/{phone_number_id}/messages`.

Without credentials it logs `[whatsapp:simulation] …` and returns `sent: false` with an explicit error. `NotificationService` stores that outcome in `notification_deliveries`, so the UI reports "not sent" rather than pretending success.

Template variables are substituted from `NotificationService::invoiceVariables()`: `{nama_penyewa}`, `{nomor_invoice}`, `{total}`, `{sisa}`, `{jatuh_tempo}`, `{kamar}`, `{organisasi}`. Defaults are seeded per organization on provisioning and editable under Settings.

### Operational notes
- The Cloud API requires pre-approved templates for business-initiated messages outside the 24-hour customer service window. The current adapter sends free-form text; production use needs an approved-template flow.
- Phone numbers must be in international format (`62…`). Stored tenant phones are local format; normalise before enabling live sending.

## Scheduled jobs that use integrations

| Command | Schedule | Behaviour |
|---|---|---|
| `nonikos:send-reminders --days=3` | daily 08:00 | Reminders for invoices due within N days |
| `nonikos:notify-ending-contracts --days=30` | Mondays 09:00 | Contract-ending notices |
| `nonikos:apply-late-fees` | daily 01:30 | Applies late fee once per invoice |
| `nonikos:mark-overdue` | daily 01:00 | Recomputes overdue status |
| `nonikos:generate-invoices` | 25th 03:00 | Creates next-period invoices |
| `nonikos:expire-agreements` | daily 02:00 | Marks lapsed contracts expired |
| `nonikos:backup` | daily 23:30 | Database dump |
| `nonikos:prune-backups --days=30` | Sundays 23:45 | Deletes expired dumps |

All are safe to re-run: invoice generation is keyed by period, late fees by `late_fee_applied_at`, notifications create a delivery row per run.

## What would be needed to go live

1. Provider account + credentials in `.env`.
2. Fix the webhook CSRF exemption noted above.
3. Approved WhatsApp templates + international phone normalisation.
4. Provider-specific signature format (the current HMAC scheme is the sandbox contract).
5. Sandbox transaction test with the provider, then a small live transaction.
6. Queue worker for outbound calls (`QUEUE_CONNECTION=database` or redis), since sends currently run synchronously.
