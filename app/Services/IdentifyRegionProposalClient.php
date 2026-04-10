<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class IdentifyRegionProposalClient
{
    public function proposeFromPath(
        string $absolutePath,
        string $filename = 'tank.jpg',
        int $maxRegions = 6
    ): array {
        $baseUrl = rtrim(config('clip.base_url'), '/');
        $timeout = (int) config('clip.timeout', 60);

        $resp = Http::timeout($timeout)
            ->attach('file', file_get_contents($absolutePath), $filename)
            ->post($baseUrl . '/propose-regions', [
                'max_regions' => max(1, min(12, $maxRegions)),
            ]);

        if (!$resp->ok()) {
            throw new RuntimeException(
                'CLIP propose-regions error: ' . $resp->status() . ' ' . $resp->body()
            );
        }

        $data = $resp->json();

        if (!isset($data['regions']) || !is_array($data['regions'])) {
            throw new RuntimeException('Invalid propose-regions response');
        }

        return $data['regions'];
    }
}