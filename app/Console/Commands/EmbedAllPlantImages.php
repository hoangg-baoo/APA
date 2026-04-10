<?php

namespace App\Console\Commands;

use App\Models\PlantImage;
use App\Services\ClipEmbeddingClient;
use Illuminate\Console\Command;

class EmbedAllPlantImages extends Command
{
    protected $signature = 'tv7:embed-plant-images {--limit=0}';
    protected $description = 'Embed all plant_images with NULL feature_vector using CLIP service';

    public function handle(ClipEmbeddingClient $clip): int
    {
        $limit = (int) $this->option('limit');

        $q = PlantImage::query()
            ->whereNull('feature_vector')
            ->orderBy('id');

        if ($limit > 0) $q->limit($limit);

        $rows = $q->get();

        if ($rows->isEmpty()) {
            $this->info('Nothing to embed.');
            return self::SUCCESS;
        }

        $this->info('Embedding: ' . $rows->count() . ' images');

        $ok = 0;
        $fail = 0;

        foreach ($rows as $row) {
            $rel = ltrim($row->image_path, '/'); // plants/xxx/1.jpg
            $abs = public_path($rel);

            if (!file_exists($abs)) {
                $this->warn("Missing file: {$row->image_path}");
                $fail++;
                continue;
            }

            try {
                $vec = $clip->embedFromPath($abs, basename($abs));
                $row->feature_vector = $vec;
                $row->save();
                $ok++;
                $this->line("OK #{$row->id} {$row->image_path}");
            } catch (\Throwable $e) {
                $fail++;
                $this->error("FAIL #{$row->id} {$row->image_path} : " . $e->getMessage());
            }
        }

        $this->info("Done. OK={$ok}, FAIL={$fail}");
        return self::SUCCESS;
    }
}
