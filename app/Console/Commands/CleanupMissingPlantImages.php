<?php

namespace App\Console\Commands;

use App\Models\PlantImage;
use Illuminate\Console\Command;

class CleanupMissingPlantImages extends Command
{
    protected $signature = 'tv7:cleanup-missing-images {--delete : Delete rows that point to missing files}';
    protected $description = 'List (and optionally delete) plant_images rows whose image file is missing';

    public function handle(): int
    {
        $delete = (bool) $this->option('delete');

        $rows = PlantImage::query()
            ->whereNull('feature_vector')
            ->orderBy('id')
            ->get(['id', 'image_path']);

        if ($rows->isEmpty()) {
            $this->info('No NULL feature_vector rows.');
            return self::SUCCESS;
        }

        $missing = [];

        foreach ($rows as $row) {
            $rel = ltrim($row->image_path, '/');
            $abs = public_path($rel);

            if (!file_exists($abs)) {
                $missing[] = $row;
            }
        }

        $this->info('Missing file rows: ' . count($missing));

        foreach ($missing as $row) {
            $this->line("#{$row->id} {$row->image_path}");
        }

        if ($delete && count($missing) > 0) {
            $ids = array_map(fn($r) => $r->id, $missing);
            PlantImage::query()->whereIn('id', $ids)->delete();
            $this->info('Deleted: ' . count($ids) . ' rows.');
        }

        return self::SUCCESS;
    }
}
