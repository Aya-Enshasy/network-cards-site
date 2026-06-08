<?php

namespace App\Services;

use App\Models\Network;
use Illuminate\Support\Str;
use RuntimeException;

class CloudinaryReceiptService
{
    public function signedUploadPayload(Network $network): array
    {
        $credentials = $this->credentials();
        $params = [
            'folder' => $this->receiptFolder($network),
            'overwrite' => 'false',
            'public_id' => $this->newPublicId(),
            'timestamp' => time(),
        ];

        $params['signature'] = $this->signature($params, $credentials['api_secret']);

        return [
            'api_key' => $credentials['api_key'],
            'cloud_name' => $credentials['cloud_name'],
            'max_bytes' => $this->maxReceiptBytes(),
            'params' => $params,
            'upload_url' => sprintf(
                'https://api.cloudinary.com/v1_1/%s/image/upload',
                rawurlencode($credentials['cloud_name'])
            ),
        ];
    }

    public function isValidReceiptReference(Network $network, string $url, string $publicId): bool
    {
        return $this->isValidReceiptUrl($url)
            && Str::startsWith(trim($publicId, '/'), $this->networkFolderPrefix($network).'/');
    }

    private function isValidReceiptUrl(string $url): bool
    {
        $credentials = $this->credentials();
        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '');

        return ($parts['scheme'] ?? '') === 'https'
            && ($parts['host'] ?? '') === 'res.cloudinary.com'
            && Str::contains($path, '/'.$credentials['cloud_name'].'/image/upload/');
    }

    private function receiptFolder(Network $network): string
    {
        return $this->networkFolderPrefix($network).'/'.now()->format('Y/m');
    }

    private function networkFolderPrefix(Network $network): string
    {
        $root = trim((string) config('services.cloudinary.receipt_folder', 'network-site/payment-receipts'), '/');
        $networkSlug = Str::slug($network->slug ?: (string) $network->id);

        if (blank($networkSlug)) {
            $networkSlug = (string) $network->id;
        }

        return $root.'/'.$networkSlug;
    }

    private function newPublicId(): string
    {
        return 'receipt-'.now()->format('YmdHis').'-'.Str::lower(Str::random(12));
    }

    /**
     * @param array<string, mixed> $params
     */
    private function signature(array $params, string $apiSecret): string
    {
        ksort($params);

        $payload = collect($params)
            ->reject(fn ($value) => $value === null || $value === '')
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode('&');

        return sha1($payload.$apiSecret);
    }

    /**
     * @return array{cloud_name: string, api_key: string, api_secret: string}
     */
    private function credentials(): array
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        $cloudinaryUrl = config('services.cloudinary.url');

        if (filled($cloudinaryUrl)) {
            $parts = parse_url((string) $cloudinaryUrl);

            $cloudName = $cloudName ?: ($parts['host'] ?? null);
            $apiKey = $apiKey ?: ($parts['user'] ?? null);
            $apiSecret = $apiSecret ?: (isset($parts['pass']) ? urldecode($parts['pass']) : null);
        }

        if (blank($cloudName) || blank($apiKey) || blank($apiSecret)) {
            throw new RuntimeException('Cloudinary receipt uploads are not configured.');
        }

        return [
            'api_key' => (string) $apiKey,
            'api_secret' => (string) $apiSecret,
            'cloud_name' => (string) $cloudName,
        ];
    }

    private function maxReceiptBytes(): int
    {
        return max(1, (int) config('services.cloudinary.max_receipt_kb', 4096)) * 1024;
    }
}
