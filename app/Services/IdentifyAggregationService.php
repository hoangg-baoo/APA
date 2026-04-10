<?php

namespace App\Services;

class IdentifyAggregationService
{
    public function mergeTopPlants(array $regionMatchPayloads, int $topK = 10): array
    {
        $bucket = [];

        foreach ($regionMatchPayloads as $payload) {
            $results = is_array($payload)
                ? ($payload['results'] ?? [])
                : [];

            $bestScorePerPlantInRegion = [];
            $metaPerPlant = [];

            foreach ($results as $row) {
                $plantId = (int) ($row['plant_id'] ?? 0);
                if ($plantId <= 0) {
                    continue;
                }

                $score = (float) ($row['score'] ?? 0);

                if (
                    !isset($bestScorePerPlantInRegion[$plantId]) ||
                    $score > $bestScorePerPlantInRegion[$plantId]
                ) {
                    $bestScorePerPlantInRegion[$plantId] = $score;

                    $metaPerPlant[$plantId] = [
                        'plant_id' => $plantId,
                        'name' => $row['name'] ?? null,
                        'difficulty' => $row['difficulty'] ?? null,
                        'light_level' => $row['light_level'] ?? null,
                        'image_sample' => $row['image_sample'] ?? null,
                        'matched_image' => $row['matched_image'] ?? null,
                    ];
                }
            }

            foreach ($bestScorePerPlantInRegion as $plantId => $score) {
                if (!isset($bucket[$plantId])) {
                    $bucket[$plantId] = [
                        'plant_id' => $metaPerPlant[$plantId]['plant_id'],
                        'name' => $metaPerPlant[$plantId]['name'],
                        'difficulty' => $metaPerPlant[$plantId]['difficulty'],
                        'light_level' => $metaPerPlant[$plantId]['light_level'],
                        'image_sample' => $metaPerPlant[$plantId]['image_sample'],
                        'matched_image' => $metaPerPlant[$plantId]['matched_image'],
                        'appear_count' => 0,
                        'scores' => [],
                        'best_score' => 0,
                    ];
                }

                $bucket[$plantId]['appear_count']++;
                $bucket[$plantId]['scores'][] = $score;
                $bucket[$plantId]['best_score'] = max(
                    $bucket[$plantId]['best_score'],
                    $score
                );
            }
        }

        $merged = array_map(function ($row) {
            $scores = $row['scores'] ?? [];
            $avg = count($scores) > 0
                ? array_sum($scores) / count($scores)
                : 0.0;

            unset($row['scores']);

            $row['avg_score'] = $avg;

            return $row;
        }, array_values($bucket));

        usort($merged, function ($a, $b) {
            return
                ($b['appear_count'] <=> $a['appear_count']) ?:
                (($b['avg_score'] ?? 0) <=> ($a['avg_score'] ?? 0)) ?:
                (($b['best_score'] ?? 0) <=> ($a['best_score'] ?? 0)) ?:
                (($a['name'] ?? '') <=> ($b['name'] ?? ''));
        });

        return array_slice($merged, 0, max(1, $topK));
    }
}