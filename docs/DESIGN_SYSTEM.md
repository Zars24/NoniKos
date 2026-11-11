# NoniKos — Design System

**Status:** implemented in `tailwind.config.js`, `resources/css/app.css`, `resources/views/components/ui`.

## Direction

Luxury Classic SaaS — Heritage Green & Champagne. Calm, premium, operational. No glassmorphism, no gradient washes, no decorative serif in tables/forms.

## Tokens

Light

| Token | Hex | Tailwind | Use |
|---|---|---|---|
| Forest Green | `#203B32` | `brand` | sidebar, primary button |
| Deep Forest | `#172B25` | `brand-deep` | hover, active nav |
| Warm Ivory | `#F6F2E9` | `ivory` | app background |
| White | `#FFFFFF` | `surface` | cards |
| Champagne | `#C4A46B` | `champagne` | accents, active marker |
| Charcoal | `#343832` | `ink` | primary text |
| Muted Sage | `#879889` | `sage` | secondary text |
| Stone | `#E5DED1` | `stone` | borders |

Dark (`.dark` class on `<html>`)

| Token | Hex | Tailwind |
|---|---|---|
| Dark Background | `#151C19` | `dark-bg` |
| Dark Surface | `#202925` | `dark-surface` |
| Dark Elevated | `#29342E` | `dark-elevated` |
| Primary text | `#F3F0E8` | `dark-ink` |
| Secondary text | `#B8C1B9` | `dark-sage` |
| Border | `#3A463E` | `dark-border` |
| Accent | `#D0B47E` | `dark-champagne` |
| Success / Warning / Danger | `#86B99A` / `#E2BE79` / `#E39A95` | semantic |

Semantic tokens: `success`, `warning`, `danger`, `info` each with `-soft` background variants, defined for both themes via CSS variables where a Tailwind class cannot be toggled.

## Theme

- `light`, `dark`, `system`; stored in `localStorage.nonikos-theme` **and** in `users.theme_preference` when logged in.
- Inline script in `<head>` applies the class before paint (no flash).
- Toggle in the navbar and in profile settings.

## Typography

Inter (self-hosted via system stack fallback: `Inter, ui-sans-serif, system-ui, ...`). Sizes: `text-xs` 12, `sm` 14, `base` 16, `lg` 18, `xl` 20, `2xl` 24, `3xl` 30. Headings `font-semibold tracking-tight`. Financial figures use `.tabular` (`font-variant-numeric: tabular-nums`).

## Spacing and shape

4 px scale. Cards: `rounded-xl border border-stone bg-white p-5 shadow-sm` (dark: `dark-border`/`dark-surface`). Inputs `rounded-lg border-stone`, focus ring champagne. Buttons `rounded-lg px-4 py-2 text-sm font-medium`.

## Components (`resources/views/components/ui`)

`card`, `stat`, `badge` (variant + label + icon, never colour-only), `table` wrapper, `input`, `select`, `textarea`, `checkbox`, `button`, `empty-state`, `page-header`, `modal` (Alpine, focus-trapped, Esc to close), `alert`, `pagination` (uses Laravel paginator), `tabs`.

## Status semantics

| Domain | Mapping |
|---|---|
| Room available / occupied / reserved / maintenance / unavailable | success / brand / champagne / warning / sage |
| Invoice draft / issued / partial / paid / overdue / cancelled | sage / info / warning / success / danger / muted |
| Payment pending / confirmed / reversed | warning / success / danger |
| Maintenance open / assigned / in progress / waiting / completed / cancelled | danger / info / warning / champagne / success / muted |

Every badge includes a text label; colour is never the only signal.

## Accessibility

Semantic landmarks, skip link, visible `focus-visible` ring, labelled inputs, `aria-invalid` + error text, `role="dialog"` + `aria-modal` on modals, keyboard-operable menus, `prefers-reduced-motion` disables transitions, minimum 44 px touch targets on mobile.
