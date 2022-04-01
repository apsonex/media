<?php

namespace Apsonex\Media\Factory;

use Apsonex\Media\Actions\DeleteImageVariationsAction;
use Apsonex\Media\Actions\ImageOptimizeAction;
use Apsonex\Media\Actions\MakeImageVariationsAction;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Image;

class Media
{

    public function imageFactory(Image|UploadedFile|string $src, ?string $targetPath = null, string $disk = null): ImageFactory
    {
        return ImageFactory::make($src)->path($targetPath)->storageDisk($disk);
    }

    public function optimizeImage(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, mixed $callback = null): bool
    {
        return ImageOptimizeAction::make($srcDisk, $srcPath, $srcTarget, $targetDisk, $callback)->optimize();
    }

    public function makeImageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null): array
    {
        return MakeImageVariationsAction::execute($path, $variations, $srcDisk, $targetDisk, $callback);
    }

    public function deleteImageVariations(string $diskDriver, array $variations, mixed $callback = null):bool
    {
        return DeleteImageVariationsAction::execute($diskDriver, $variations, $callback);
    }

}