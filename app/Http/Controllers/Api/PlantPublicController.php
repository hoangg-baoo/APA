<?php

namespace App\Http\Controllers\Api;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantPublicController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Plant::query();

        if ($q = trim((string)$request->query('q', ''))) {
            $query->where('name', 'like', '%' . $q . '%');
        }

        if ($light = trim((string)$request->query('light', ''))) {
            $query->where('light_level', $light);
        }

        if ($difficulty = trim((string)$request->query('difficulty', ''))) {
            $query->where('difficulty', $difficulty);
        }

        $plants = $query
            ->orderBy('name')
            ->limit(500)
            ->get([
                'id',
                'name',
                'difficulty',
                'light_level',
                'ph_min',
                'ph_max',
                'temp_min',
                'temp_max',
                'image_sample',
            ]);

        return $this->success($plants, 'OK');
    }

    public function show(Request $request, Plant $plant)
    {
        $plant->load(['images' => function ($q) {
            $q->orderBy('id');
        }]);

        $data = [
            'id' => $plant->id,
            'name' => $plant->name,
            'description' => $plant->description,
            'ph_min' => $plant->ph_min,
            'ph_max' => $plant->ph_max,
            'temp_min' => $plant->temp_min,
            'temp_max' => $plant->temp_max,
            'light_level' => $plant->light_level,
            'difficulty' => $plant->difficulty,
            'image_sample' => $plant->image_sample,
            'care_guide' => $plant->care_guide,
            'images' => $plant->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image_path' => $img->image_path,
                ];
            })->values(),
        ];

        return $this->success($data, 'OK');
    }
}
