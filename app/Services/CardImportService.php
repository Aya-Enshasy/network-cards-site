<?php

namespace App\Services;

use App\Imports\CardCodesImport;
use App\Models\CardImport;
use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CardImportService
{
    /**
     * @return array{imported:int, duplicates:int, failed:int, errors:array<int, string>, history:CardImport}
     */
    public function import(UploadedFile $file, Network $network, CardPackage $package, ?int $uploadedBy = null): array
    {
        if ($package->network_id !== $network->id) {
            throw ValidationException::withMessages([
                'package_id' => 'الباقة المختارة لا تتبع هذه الشبكة.',
            ]);
        }

        $rows = $this->parseCardRows($this->readRows($file));

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'الملف لا يحتوي على بطاقات قابلة للاستيراد. الصيغة المدعومة: Username / Password / Package في 3 صفوف، وكل بطاقة في زوج أعمدة.',
            ]);
        }

        $seenInFile = [];
        $imported = 0;
        $duplicates = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $network, $package, &$seenInFile, &$imported, &$duplicates, &$failed, &$errors): void {
            foreach ($rows as $index => $row) {
                $displayLine = (int) ($row['line'] ?? ($index + 1));
                $cardCode = $this->cleanCell($row['code'] ?? null);
                $cardPassword = $this->cleanCell($row['password'] ?? null);
                $packageLabel = $this->cleanCell($row['package_label'] ?? null);

                if ($cardCode === '' || $cardPassword === '') {
                    $failed++;
                    $errors[] = "السطر {$displayLine}: يجب إدخال رقم البطاقة وكلمة السر معًا.";
                    continue;
                }

                $dedupeKey = Str::upper($cardCode);

                if (isset($seenInFile[$dedupeKey])) {
                    $duplicates++;
                    $errors[] = "السطر {$displayLine}: رقم البطاقة {$cardCode} مكرر داخل الملف.";
                    continue;
                }

                $seenInFile[$dedupeKey] = true;

                $created = HotspotCard::firstOrCreate(
                    [
                        'network_id' => $network->id,
                        'card_code' => $cardCode,
                    ],
                    [
                        'package_id' => $package->id,
                        'card_password' => $cardPassword,
                        'package_label' => $packageLabel !== '' ? $packageLabel : null,
                        'status' => 'available',
                    ],
                );

                if ($created->wasRecentlyCreated) {
                    $imported++;
                    continue;
                }

                $duplicates++;
                $errors[] = "السطر {$displayLine}: رقم البطاقة {$cardCode} موجود مسبقًا.";
            }
        });

        $history = CardImport::create([
            'network_id' => $network->id,
            'package_id' => $package->id,
            'uploaded_by' => $uploadedBy,
            'file_name' => $file->getClientOriginalName(),
            'imported_count' => $imported,
            'duplicate_count' => $duplicates,
            'failed_count' => $failed,
            'sample_errors' => array_slice($errors, 0, 12),
        ]);

        return [
            'imported' => $imported,
            'duplicates' => $duplicates,
            'failed' => $failed,
            'errors' => $errors,
            'history' => $history,
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(UploadedFile $file): array
    {
        try {
            $import = new CardCodesImport();
            Excel::import($import, $file);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'تعذر قراءة ملف Excel. تأكد أن الملف بصيغة xlsx أو xls أو csv.',
            ]);
        }

        return $import->rows
            ->map(fn ($row) => $row instanceof Collection ? $row->values()->all() : collect($row)->values()->all())
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, mixed>>  $rawRows
     * @return array<int, array{line:int, column?:int, code:string, password:string, package_label:string}>
     */
    private function parseCardRows(array $rawRows): array
    {
        $packedCellCards = $this->parsePackedCellLayout($rawRows);

        if ($packedCellCards !== []) {
            return $packedCellCards;
        }

        $blockCards = $this->parseBlockLayout($rawRows);

        if ($blockCards !== []) {
            return $blockCards;
        }

        return $this->parseSimpleRows($rawRows);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rawRows
     * @return array<int, array{line:int, column:int, code:string, password:string, package_label:string}>
     */
    private function parsePackedCellLayout(array $rawRows): array
    {
        $cards = [];

        foreach ($rawRows as $rowIndex => $row) {
            foreach (array_values($row) as $column => $cell) {
                $text = trim((string) $cell);

                if ($text === '' || ! Str::of($text)->lower()->contains(['username', 'password', 'package'])) {
                    continue;
                }

                $lines = preg_split('/\R/u', $text) ?: [];

                for ($lineIndex = 0; $lineIndex <= count($lines) - 3; $lineIndex++) {
                    $code = $this->extractLabeledValue($lines[$lineIndex], 'username');

                    if ($code === null) {
                        continue;
                    }

                    $password = $this->extractLabeledValue($lines[$lineIndex + 1], 'password');
                    $packageLabel = $this->extractLabeledValue($lines[$lineIndex + 2], 'package');

                    if ($password === null || $packageLabel === null) {
                        continue;
                    }

                    $cards[] = [
                        'line' => $rowIndex + 1,
                        'column' => $column + 1,
                        'code' => $code,
                        'password' => $password,
                        'package_label' => $packageLabel,
                    ];

                    $lineIndex += 2;
                }
            }
        }

        return $cards;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rawRows
     * @return array<int, array{line:int, column:int, code:string, password:string, package_label:string}>
     */
    private function parseBlockLayout(array $rawRows): array
    {
        $cards = [];
        $rowCount = count($rawRows);

        for ($rowIndex = 0; $rowIndex <= $rowCount - 3; $rowIndex++) {
            $usernameRow = $rawRows[$rowIndex] ?? [];
            $passwordRow = $rawRows[$rowIndex + 1] ?? [];
            $packageRow = $rawRows[$rowIndex + 2] ?? [];
            $maxColumns = max(count($usernameRow), count($passwordRow), count($packageRow));

            for ($column = 0; $column <= $maxColumns - 2; $column++) {
                if (! $this->isUsernameLabel($usernameRow[$column] ?? null)) {
                    continue;
                }

                if (! $this->isPasswordLabel($passwordRow[$column] ?? null)) {
                    continue;
                }

                if (! $this->isPackageLabel($packageRow[$column] ?? null)) {
                    continue;
                }

                $code = $this->cleanCell($usernameRow[$column + 1] ?? null);
                $password = $this->cleanCell($passwordRow[$column + 1] ?? null);
                $packageLabel = $this->cleanCell($packageRow[$column + 1] ?? null);

                if ($code === '' && $password === '') {
                    continue;
                }

                $cards[] = [
                    'line' => $rowIndex + 1,
                    'column' => $column + 1,
                    'code' => $code,
                    'password' => $password,
                    'package_label' => $packageLabel,
                ];
            }
        }

        return $cards;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rawRows
     * @return array<int, array{line:int, code:string, password:string, package_label:string}>
     */
    private function parseSimpleRows(array $rawRows): array
    {
        $cards = [];

        foreach ($rawRows as $rowIndex => $row) {
            $values = array_values($row);
            $code = $this->cleanCell($values[0] ?? null);
            $password = $this->cleanCell($values[1] ?? null);
            $packageLabel = $this->cleanCell($values[2] ?? null);

            if (($code === '' && $password === '' && $packageLabel === '') || $this->looksLikeHeader($code)) {
                continue;
            }

            $cards[] = [
                'line' => $rowIndex + 1,
                'code' => $code,
                'password' => $password,
                'package_label' => $packageLabel,
            ];
        }

        return $cards;
    }

    private function cleanCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            $value = number_format($value, 0, '.', '');
        }

        $text = trim((string) $value);

        return preg_replace('/\s+/u', ' ', $text) ?: '';
    }

    private function extractLabeledValue(string $line, string $label): ?string
    {
        $line = $this->cleanCell($line);

        $patterns = [
            'username' => '/^(?:username|user|login|card\s*(?:code|number)|number)\s*[:\-]?\s*(.+)$/iu',
            'password' => '/^(?:password|pass|pin)\s*[:\-]?\s*(.+)$/iu',
            'package' => '/^(?:package|pkg|plan|subscription)\s*[:\-]?\s*(.+)$/iu',
        ];

        if (! isset($patterns[$label]) || ! preg_match($patterns[$label], $line, $matches)) {
            return null;
        }

        return $this->cleanCell($matches[1] ?? null);
    }

    private function normalizeLabel(mixed $value): string
    {
        return Str::of($this->cleanCell($value))
            ->lower()
            ->replace([' ', '-', '_', ':'], '')
            ->toString();
    }

    private function isUsernameLabel(mixed $value): bool
    {
        return in_array($this->normalizeLabel($value), [
            'username',
            'user',
            'login',
            'cardcode',
            'cardnumber',
            'number',
            'اسمالمستخدم',
            'رقمالبطاقة',
        ], true);
    }

    private function isPasswordLabel(mixed $value): bool
    {
        return in_array($this->normalizeLabel($value), [
            'password',
            'pass',
            'pin',
            'كلمةالسر',
            'السر',
        ], true);
    }

    private function isPackageLabel(mixed $value): bool
    {
        return in_array($this->normalizeLabel($value), [
            'package',
            'pkg',
            'plan',
            'subscription',
            'الباقة',
            'باقة',
            'الاشتراك',
        ], true);
    }

    private function looksLikeHeader(string $code): bool
    {
        return in_array($this->normalizeLabel($code), [
            'cardcode',
            'code',
            'hotspotcode',
            'cardnumber',
            'number',
            'username',
            'رقمالبطاقة',
            'اسمالمستخدم',
        ], true);
    }
}
