<?php

namespace App\Services;

use App\Models\CardImport;
use App\Models\CardPackage;
use App\Models\HotspotCard;
use App\Models\Network;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

        $networkPackages = $network->packages()
            ->where('active', true)
            ->get();
        $preparedRows = [];

        foreach ($rows as $index => $row) {
            $displayLine = (int) ($row['line'] ?? ($index + 1));
            $cardCode = $this->cleanCell($row['code'] ?? null);
            $cardPassword = $this->cleanCell($row['password'] ?? null);
            $packageLabel = $this->cleanCell($row['package_label'] ?? null);
            $resolvedPackage = $this->resolvePackage($packageLabel, $networkPackages, $package);

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
            $preparedRows[] = [
                'line' => $displayLine,
                'dedupe_key' => $dedupeKey,
                'network_id' => $network->id,
                'package_id' => $resolvedPackage->id,
                'card_code' => $cardCode,
                'card_password' => $cardPassword,
                'package_label' => $packageLabel !== '' ? $packageLabel : null,
                'imported_at' => now(),
                'status' => 'available',
            ];
        }

        if ($preparedRows !== []) {
            $existingCards = collect(array_column($preparedRows, 'card_code'))
                ->chunk(500)
                ->flatMap(fn (Collection $cardCodes): Collection => HotspotCard::query()
                    ->where('network_id', $network->id)
                    ->whereIn('card_code', $cardCodes->all())
                    ->pluck('card_code'))
                ->mapWithKeys(fn (string $cardCode): array => [Str::upper($cardCode) => true])
                ->all();

            $newRows = [];
            $timestamp = now();

            foreach ($preparedRows as $row) {
                if (isset($existingCards[$row['dedupe_key']])) {
                    $duplicates++;
                    $errors[] = "السطر {$row['line']}: رقم البطاقة {$row['card_code']} موجود مسبقًا.";
                    continue;
                }

                unset($row['line'], $row['dedupe_key']);

                $newRows[] = [
                    ...$row,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            foreach (array_chunk($newRows, 300) as $chunk) {
                $inserted = DB::table('hotspot_cards')->insertOrIgnore($chunk);
                $imported += (int) $inserted;
                $duplicates += count($chunk) - (int) $inserted;
            }
        }

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
     * @return array<int, array{sheet:string, rows:array<int, array<int, mixed>>}>
     */
    private function readRows(UploadedFile $file): array
    {
        try {
            $path = $file->getRealPath() ?: $file->getPathname();
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'تعذر قراءة ملف Excel. تأكد أن الملف بصيغة xlsx أو xls أو csv.',
            ]);
        }

        $worksheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $rows = [];

            for ($row = 1; $row <= $highestRow; $row++) {
                $values = [];

                for ($column = 1; $column <= $highestColumn; $column++) {
                    $values[] = $sheet->getCellByColumnAndRow($column, $row)->getFormattedValue();
                }

                $rows[] = $values;
            }

            $worksheets[] = [
                'sheet' => $sheet->getTitle(),
                'rows' => $rows,
            ];
        }

        return $worksheets;
    }

    /**
     * @param  array<int, array{sheet:string, rows:array<int, array<int, mixed>>}>  $worksheets
     * @return array<int, array{line:int, column?:int, code:string, password:string, package_label:string}>
     */
    private function parseCardRows(array $worksheets): array
    {
        $cards = [];

        foreach ($worksheets as $worksheet) {
            $rawRows = $worksheet['rows'] ?? [];
            $packedCellCards = $this->parsePackedCellLayout($rawRows);

            if ($packedCellCards !== []) {
                array_push($cards, ...$packedCellCards);

                continue;
            }

            $blockCards = $this->parseBlockLayout($rawRows);

            if ($blockCards !== []) {
                array_push($cards, ...$blockCards);

                continue;
            }

            array_push($cards, ...$this->parseSimpleRows($rawRows));
        }

        return $cards;
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

    private function resolvePackage(string $packageLabel, Collection $packages, CardPackage $fallback): CardPackage
    {
        if ($packageLabel === '') {
            return $fallback;
        }

        $numbers = $this->extractNumbers($packageLabel);

        if (count($numbers) >= 2) {
            $duration = (int) $numbers[0];
            $price = (float) $numbers[array_key_last($numbers)];
            $matched = $packages->first(
                fn (CardPackage $candidate): bool => (int) $candidate->duration_hours === $duration
                    && abs(((float) $candidate->price) - $price) < 0.01
            );

            if ($matched instanceof CardPackage) {
                return $matched;
            }
        }

        $normalizedLabel = $this->normalizePackageText($packageLabel);
        $matched = $packages->first(function (CardPackage $candidate) use ($normalizedLabel): bool {
            $name = $this->normalizePackageText($candidate->name);
            $description = $this->normalizePackageText($candidate->description ?? '');

            return ($name !== '' && str_contains($normalizedLabel, $name))
                || ($description !== '' && str_contains($normalizedLabel, $description));
        });

        return $matched instanceof CardPackage ? $matched : $fallback;
    }

    /**
     * @return array<int, float>
     */
    private function extractNumbers(string $value): array
    {
        $value = strtr($value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);

        preg_match_all('/\d+(?:[\.,]\d+)?/u', $value, $matches);

        return collect($matches[0] ?? [])
            ->map(fn (string $number): float => (float) str_replace(',', '.', $number))
            ->values()
            ->all();
    }

    private function normalizePackageText(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace([' ', '-', '_', ':', '/', '\\', '.', '،', ','], '')
            ->toString();
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
