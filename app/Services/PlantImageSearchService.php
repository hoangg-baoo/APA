<?php

namespace App\Services;

use App\Models\PlantImage;

class PlantImageSearchService
{
    public function search(array $queryVector, int $topK = 5): array
    {
        $images = PlantImage::query()
            ->where('purpose', 'library')
            ->whereNotNull('feature_vector')
            ->with('plant')
            ->get();

        $bestByPlant = [];

        foreach ($images as $img) {
            $vec = $img->feature_vector;

            if (!is_array($vec) || empty($vec)) {
                continue;
            }

            $score = $this->cosine($queryVector, $vec);
            if ($score === null) {
                continue;
            }

            $plant = $img->plant;
            if (!$plant || !$plant->id) {
                continue;
            }

            $plantId = (int) $plant->id;

            $row = [
                'plant_id'      => $plantId,
                'name'          => $plant->name,
                'difficulty'    => $plant->difficulty ?? null,
                'light_level'   => $plant->light_level ?? null,
                'image_sample'  => $plant->image_sample ?? null,
                'matched_image' => $img->image_path,
                'score'         => $score,
            ];

            if (!isset($bestByPlant[$plantId]) || $score > ($bestByPlant[$plantId]['score'] ?? -INF)) {
                $bestByPlant[$plantId] = $row;
            }
        }

        $results = array_values($bestByPlant);

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, max(1, $topK));
    }

    private function cosine(array $a, array $b): ?float
    {
        $n = min(count($a), count($b));

        if ($n <= 0) {
            return null;
        }

        $dot = 0.0;
        $na  = 0.0;
        $nb  = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];

            $dot += $x * $y;
            $na  += $x * $x;
            $nb  += $y * $y;
        }

        if ($na <= 0 || $nb <= 0) {
            return null;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}