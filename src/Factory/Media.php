<?php

namespace Apsonex\Media\Factory;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;
use Illuminate\Http\UploadedFile;
use Apsonex\Media\Actions\ImageOptimizeAction;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Actions\MakeImageVariationsAction;
use Apsonex\Media\Actions\DeleteImageVariationsAction;

class Media
{

    public function imageFactory(Image|UploadedFile|string $src, ?string $targetPath = null, string $disk = null): ImageFactory
    {
        return ImageFactory::make($src)->path($targetPath)->storageDisk($disk);
    }

    public function imageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, mixed $callback = null): bool
    {
        return ImageOptimizeAction::make($srcDisk, $srcPath, $srcTarget, $targetDisk, $callback)->optimize();
    }

    public function queueImageOptimize(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, mixed $callback = null)
    {
        ImageOptimizeAction::queue($srcDisk, $srcPath, $srcTarget, $targetDisk, $callback);
    }

    public function imageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null): array
    {
        return MakeImageVariationsAction::execute($path, $variations, $srcDisk, $targetDisk, $callback);
    }

    public function queueImageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null)
    {
        MakeImageVariationsAction::queue($path, $variations, $srcDisk, $targetDisk, $callback);
    }

    public function deleteImageVariations(string $diskDriver, array $variations, mixed $callback = null): bool
    {
        return DeleteImageVariationsAction::execute($diskDriver, $variations, $callback);
    }

    public function queueDeleteImageVariations(string $diskDriver, array $variations, mixed $callback = null)
    {
        DeleteImageVariationsAction::queue($diskDriver, $variations, $callback);
    }

    public static function deleteDirectoriesIfEmpty(string|Filesystem $disk, array|string $paths)
    {
        $disk = is_string($disk) ? Storage::disk($disk) : $disk;

        collect(Arr::wrap($paths))
            ->map(fn($p) => pathinfo($p)['dirname'] ?? null)
            ->filter()
            ->filter(fn($p) => $p !== '.')
            ->unique()
            ->map(function ($dir) use ($disk) {
                $files = $disk->files($dir);
                $dirs = $disk->directories($dir);
                if (empty($files) && empty($dirs)) {
                    $disk->deleteDirectory($dir);
                }
            });
    }

}