<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Options;

class ImportLeadsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;
    public int $tries = 1;

    private const CHUNK_SIZE = 500;

    public function __construct(private readonly int $importId) {}

    public function handle(): void
    {
        $import = Import::findOrFail($this->importId);
        $import->update(['status' => 'processing']);

        $filePath = Storage::disk('local')->path($import->filename);

        try {
            $options = new Options();
            $options->SHOULD_FORMAT_DATES = false;

            $reader = new Reader($options);
            $reader->open($filePath);

            $totalRows = 0;
            $processedRows = 0;
            $failedRows = 0;
            $isFirstRow = true;
            $chunk = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if ($isFirstRow) {
                        $isFirstRow = false;
                        continue;
                    }

                    $totalRows++;
                    $cells = $row->getCells();
                    $chunk[] = $this->mapRowToData($cells);

                    if (count($chunk) >= self::CHUNK_SIZE) {
                        ['inserted' => $ins, 'failed' => $fail] = $this->insertChunk($chunk);
                        $processedRows += $ins;
                        $failedRows += $fail;
                        $chunk = [];

                        $import->update([
                            'total_rows'     => $totalRows,
                            'processed_rows' => $processedRows,
                            'failed_rows'    => $failedRows,
                        ]);
                    }
                }
                break;
            }

            if (!empty($chunk)) {
                ['inserted' => $ins, 'failed' => $fail] = $this->insertChunk($chunk);
                $processedRows += $ins;
                $failedRows += $fail;
            }

            $reader->close();

            $import->update([
                'status'         => 'done',
                'total_rows'     => $totalRows,
                'processed_rows' => $processedRows,
                'failed_rows'    => $failedRows,
            ]);
        } catch (\Throwable $e) {
            Log::error('Import failed', ['import_id' => $this->importId, 'error' => $e->getMessage()]);
            $import->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function mapRowToData(array $cells): array
    {
        $now = now()->toDateTimeString();

        return [
            'external_id'       => $this->cellValue($cells[0] ?? null),
            'created_at_source' => $this->parseDateCell($cells[1] ?? null),
            'first_name'        => $this->cellValue($cells[2] ?? null),
            'last_name'         => $this->cellValue($cells[3] ?? null),
            'phone'             => $this->cellValue($cells[4] ?? null),
            'email'             => $this->cellValue($cells[5] ?? null),
            'city'              => $this->cellValue($cells[6] ?? null),
            'source'            => $this->cellValue($cells[7] ?? null),
            'utm_campaign'      => $this->cellValue($cells[8] ?? null),
            'product'           => $this->cellValue($cells[9] ?? null),
            'budget_uah'        => $this->nullableDecimal($this->cellValue($cells[10] ?? null)),
            'status'            => $this->cellValue($cells[11] ?? null),
            'manager'           => $this->cellValue($cells[12] ?? null),
            'comment'           => $this->cellValue($cells[13] ?? null),
            'next_contact_at'   => $this->parseDateCell($cells[14] ?? null),
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
    }

    private function insertChunk(array $chunk): array
    {
        $inserted = 0;
        $failed = 0;

        try {
            DB::table('leads')->upsert(
                $chunk,
                ['external_id'],
                [
                    'created_at_source', 'first_name', 'last_name', 'phone', 'email',
                    'city', 'source', 'utm_campaign', 'product', 'budget_uah',
                    'status', 'manager', 'comment', 'next_contact_at', 'updated_at',
                ]
            );
            $inserted = count($chunk);
        } catch (\Throwable $e) {
            // Fallback: insert row by row to maximise successful rows
            foreach ($chunk as $row) {
                try {
                    DB::table('leads')->upsert(
                        [$row],
                        ['external_id'],
                        [
                            'created_at_source', 'first_name', 'last_name', 'phone', 'email',
                            'city', 'source', 'utm_campaign', 'product', 'budget_uah',
                            'status', 'manager', 'comment', 'next_contact_at', 'updated_at',
                        ]
                    );
                    $inserted++;
                } catch (\Throwable) {
                    $failed++;
                }
            }
        }

        return ['inserted' => $inserted, 'failed' => $failed];
    }

    private function cellValue(mixed $cell): ?string
    {
        if ($cell === null) {
            return null;
        }
        $value = $cell->getValue();
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        return (string) $value;
    }

    private function parseDateCell(mixed $cell): ?string
    {
        if ($cell === null) {
            return null;
        }
        $value = $cell->getValue();
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            $str = (string) $value;
            $ts = strtotime($str);
            return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return is_numeric($value) ? (string) $value : null;
    }
}
