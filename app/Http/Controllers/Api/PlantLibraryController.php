<?php

namespace App\Http\Controllers\Api;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantLibraryController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Plant::query();

        if ($q = trim((string)$request->query('q', ''))) {
            $query->where('name', 'like', '%' . $q . '%');
        }

        $plants = $query
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'difficulty', 'light_level', 'ph_min', 'ph_max']);

        return $this->success($plants, 'OK');
    }
}
