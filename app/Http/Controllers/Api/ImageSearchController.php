<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageSearchRequest;
use App\Models\PlantImage;
use App\Services\ClipEmbeddingClient;

class ImageSearchController extends Controller
{
    private function dot(array $a, array $b): float
    {
        $sum = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $sum += ((float) $a[$i]) * ((float) $b[$i]);
        }
        return $sum;
    }

    public function search(ImageSearchRequest $request, ClipEmbeddingClient $clip)
    {
        $topK = (int) ($request->input('top_k', 5));
        $file = $request->file('image');

        $queryVec = $clip->embedFromUploadedFile($file);

        $images = PlantImage::query()
            ->whereNotNull('feature_vector')
            ->with('plant:id,name,light_level,difficulty,image_sample')
            ->get();

        if ($images->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No embedded plant images found.',
            ], 422);
        }

        $bestByPlant = []; // plant_id => best match

        foreach ($images as $img) {
            $vec = $img->feature_vector;
            if (!is_array($vec) || count($vec) === 0) continue;

            $score = $this->dot($queryVec, $vec); // normalized => cosine = dot
            $pid = (int) $img->plant_id;

            if (!isset($bestByPlant[$pid]) || $score > $bestByPlant[$pid]['score']) {
                $bestByPlant[$pid] = [
                    'score' => $score,
                    'matched_image' => $img->image_path,
                    'plant' => $img->plant,
                ];
            }
        }

        $list = array_values($bestByPlant);

        usort($list, function ($x, $y) {
            return $y['score'] <=> $x['score'];
        });

        $list = array_slice($list, 0, $topK);

        $results = array_map(function ($x) {
            $p = $x['plant'];
            return [
                'plant_id' => $p?->id,
                'name' => $p?->name,
                'image_sample' => $p?->image_sample,
                'matched_image' => $x['matched_image'],
                'score' => round((float) $x['score'], 6),
                'light_level' => $p?->light_level,
                'difficulty' => $p?->difficulty,
            ];
        }, $list);

        return response()->json([
            'success' => true,
            'data' => [
                'top_k' => $topK,
                'results' => $results,
            ],
            'message' => 'OK',
        ]);
    }
}
