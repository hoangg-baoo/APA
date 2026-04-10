<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StorePlantRequest;
use App\Http\Requests\UpdatePlantRequest;
use App\Models\Plant;
use App\Models\PlantImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PlantAdminController extends BaseApiController
{
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $difficulty = trim((string)$request->query('difficulty', ''));
        $light = trim((string)$request->query('light_level', ''));

        $perPage = (int)$request->query('per_page', 20);
        if ($perPage <= 0 || $perPage > 100) $perPage = 20;

        // ✅ sort direction: asc|desc (default asc để ID 1 lên trước)
        $dir = strtolower(trim((string)$request->query('dir', 'asc')));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

        // ✅ FIX: lấy ảnh đầu tiên bằng subquery -> không bị ambiguous plant_id
        $plants = Plant::query()
            ->select('plants.*')
            ->addSelect([
                'first_image_path' => PlantImage::query()
                    ->select('image_path')
                    ->whereColumn('plant_images.plant_id', 'plants.id')
                    ->orderBy('plant_images.id')
                    ->limit(1),
            ])
            ->when($q !== '', fn($qr) => $qr->where('plants.name', 'like', "%{$q}%"))
            ->when($difficulty !== '', fn($qr) => $qr->where('plants.difficulty', $difficulty))
            ->when($light !== '', fn($qr) => $qr->where('plants.light_level', $light))
            ->orderBy('plants.id', $dir)
            ->paginate($perPage)
            ->appends($request->query());

        // thêm thumb url để UI render ảnh
        $plants->getCollection()->transform(function ($p) {
            $path = $p->image_sample ?: ($p->first_image_path ?? null);
            $p->thumb = $path ? asset($path) : null;
            return $p;
        });

        return $this->success($plants);
    }

    public function store(StorePlantRequest $request)
    {
        $data = $request->validated();
        /** @var UploadedFile|null $file */
        $file = $request->file('image_file');
        unset($data['image_file']);

        $plant = Plant::create($data);

        if ($file) {
            $path = $this->storePlantImage($plant, $file, true);
            $plant->image_sample = $path;
            $plant->save();
        }

        return $this->success($plant->fresh(), 'Plant created.');
    }

    public function show(Plant $plant)
    {
        $first = PlantImage::query()
            ->where('plant_id', $plant->id)
            ->orderBy('id')
            ->value('image_path');

        $path = $plant->image_sample ?: $first;
        $plant->thumb = $path ? asset($path) : null;

        return $this->success($plant);
    }

    public function update(UpdatePlantRequest $request, Plant $plant)
    {
        $data = $request->validated();
        /** @var UploadedFile|null $file */
        $file = $request->file('image_file');
        unset($data['image_file']);

        $plant->update($data);

        if ($file) {
            $path = $this->storePlantImage($plant, $file, true);
            $plant->image_sample = $path; // ✅ upload mới thì set làm cover luôn
            $plant->save();
        }

        return $this->success($plant->fresh(), 'Plant updated.');
    }

    public function destroy(Plant $plant)
    {
        $plant->delete();
        return $this->success(null, 'Plant deleted.');
    }

    private function storePlantImage(Plant $plant, UploadedFile $file, bool $createDbRow = true): string
    {
        $slug = Str::slug($plant->name, '_');
        $dir = public_path('plants/' . $slug);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $count = PlantImage::where('plant_id', $plant->id)->count();
        $next = $count + 1;

        $filename = $next . '.' . $ext;
        $file->move($dir, $filename);

        $path = 'plants/' . $slug . '/' . $filename;

        if ($createDbRow) {
            PlantImage::create([
                'plant_id' => $plant->id,
                'image_path' => $path,
                'feature_vector' => null,
            ]);
        }

        return $path;
    }
}
