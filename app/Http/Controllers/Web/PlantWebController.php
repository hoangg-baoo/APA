<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\Request;

class PlantWebController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $group = trim((string) $request->query('group', '')); // slug, e.g. alternanthera-reineckii

        $plantsQuery = Plant::query();

        // Filter by group (Genus + species) from slug
        if ($group !== '') {
            $prefix = str_replace('-', ' ', strtolower($group)); // alternanthera reineckii
            $plantsQuery->whereRaw('LOWER(name) LIKE ?', [$prefix . '%']);
        }

        if ($q !== '') {
            $plantsQuery->where('name', 'like', '%' . $q . '%');
        }

        $plants = $plantsQuery
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('plants.index', [
            'plants' => $plants,
            'q' => $q,
            'group' => $group,
        ]);
    }

    public function show(int $plant)
    {
        $plant = Plant::with(['images'])
            ->findOrFail($plant);

        return view('plants.show', [
            'plant' => $plant,
        ]);
    }
}
