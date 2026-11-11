<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ReportController extends Controller
{
    public function finance(Request $request, ReportService $reports)
    {
        [$from, $to] = $this->range($request);
        $propertyId = $request->integer('property_id') ?: null;

        $summary = $reports->summary($from, $to, $propertyId);
        $series = $reports->monthlySeries($from, $to, $propertyId);
        $aging = $reports->aging($propertyId);
        $properties = Property::orderBy('name')->get(['id', 'name']);

        return view('reports.finance', compact('summary', 'series', 'aging', 'properties', 'from', 'to', 'propertyId'));
    }

    /**
     * CSV/XLSX export. Requires finance.exports.create on top of finance.reports.view.
     */
    public function export(Request $request, ReportService $reports, string $format)
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        [$from, $to] = $this->range($request);
        $propertyId = $request->integer('property_id') ?: null;
        $rows = $reports->exportRows($from, $to, $propertyId);
        $filename = 'laporan-keuangan-'.$from->toDateString().'-'.$to->toDateString().'.'.$format;

        app(ActivityLogger::class)->log('finance.exported', null, 'Laporan keuangan diekspor ('.$format.')', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'property_id' => $propertyId,
            'rows' => count($rows),
        ]);

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
                if ($rows) {
                    fputcsv($out, array_keys($rows[0]));
                }
                foreach ($rows as $row) {
                    fputcsv($out, array_map([$this, 'csvSafe'], $row));
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->streamDownload(function () use ($rows) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            if ($rows) {
                $writer->addRow(Row::fromValues(array_keys($rows[0])));
            }
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_map(
                    fn ($v) => is_scalar($v) || $v === null ? $this->csvSafe($v) : (string) $v,
                    array_values($row)
                )));
            }

            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Neutralise CSV formula injection: a cell beginning with = + - @ (or tab/CR)
     * is executed as a formula by Excel/LibreOffice. Prefixing a single quote
     * keeps the text readable and inert.
     */
    protected function csvSafe(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    protected function range(Request $request): array
    {        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : today()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : today()->endOfMonth();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        // Guard against unbounded ranges that would scan everything.
        if ($from->diffInMonths($to) > 24) {
            $from = $to->copy()->subMonths(24)->startOfMonth();
        }

        return [$from, $to];
    }
}