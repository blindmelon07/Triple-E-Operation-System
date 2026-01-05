<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function export(array $headers, Collection $data, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $data) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8 Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Write headers
            fputcsv($handle, $headers);

            // Write data rows
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
