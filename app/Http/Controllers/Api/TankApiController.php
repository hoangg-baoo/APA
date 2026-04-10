<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTankRequest;
use App\Http\Requests\UpdateTankRequest;
use App\Models\Tank;
use Illuminate\Http\Request;

class TankApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $tanks = Tank::query()
            ->where('user_id', $userId)
            ->withCount('tankPlants')
            ->orderByDesc('created_at')
            ->get();

        return $this->success($tanks);
    }

    public function store(StoreTankRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        // default co2
        if (!array_key_exists('co2', $data) || $data['co2'] === null || $data['co2'] === '') {
            $data['co2'] = 'none';
        }

        // if size parts exist => build size string
        $L = $data['length_cm'] ?? null;
        $W = $data['width_cm'] ?? null;
        $H = $data['height_cm'] ?? null;
        if ($L !== null && $W !== null && $H !== null) {
            $data['size'] = "{$L}×{$W}×{$H}";
        } else {
            $data['size'] = null;
        }

        $tank = Tank::create($data);

        return $this->success($tank, 'Tank created.');
    }

    public function show(Request $request, Tank $tank)
    {
        $this->authorize('view', $tank);

        $tank->load([
            'tankPlants.plant',
            'waterLogs' => fn ($q) => $q->orderByDesc('logged_at')->limit(50),
        ])->loadCount('tankPlants');

        return $this->success($tank);
    }

    public function update(UpdateTankRequest $request, Tank $tank)
    {
        $this->authorize('update', $tank);

        $data = $request->validated();

        // default co2 if client sends null/empty
        if (array_key_exists('co2', $data) && ($data['co2'] === null || $data['co2'] === '')) {
            $data['co2'] = 'none';
        }

        // rebuild size if all 3 are present in payload (or keep old)
        $hasAnySizeField =
            array_key_exists('length_cm', $data) ||
            array_key_exists('width_cm', $data) ||
            array_key_exists('height_cm', $data);

        if ($hasAnySizeField) {
            $L = $data['length_cm'] ?? $tank->length_cm;
            $W = $data['width_cm'] ?? $tank->width_cm;
            $H = $data['height_cm'] ?? $tank->height_cm;

            if ($L !== null && $W !== null && $H !== null) {
                $data['size'] = "{$L}×{$W}×{$H}";
            } else {
                $data['size'] = null;
            }
        }

        $tank->update($data);

        return $this->success($tank->fresh(), 'Tank updated.');
    }

    public function destroy(Request $request, Tank $tank)
    {
        $this->authorize('delete', $tank);

        $tank->delete();

        return $this->success(null, 'Tank deleted.');
    }
}
