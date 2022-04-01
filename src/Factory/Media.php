<?php

namespace Apsonex\Media\Factory;

use Apsonex\Media\Actions\ImageOptimizeAction;
use Apsonex\Media\Actions\MakeImageVariationsAction;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Image;

class Media
{

    public function imageFactory(Image|UploadedFile|string $src, ?string $targetPath = null, ?Filesystem $disk = null): ImageFactory
    {
        return ImageFactory::make($src)->path($targetPath)->storageDisk($disk);
    }


    public function imageOptimizer(string $srcDisk, string $srcPath, string $srcTarget = null, string $targetDisk = null, mixed $callback = null): ImageOptimizeAction
    {
        return ImageOptimizeAction::make($srcDisk, $srcPath, $srcTarget, $targetDisk, $callback);
    }


    public function makeImageVariations(string $path, array $variations, string $srcDisk, string $targetDisk = null, $callback = null)
    {
        return MakeImageVariationsAction::execute($path, $variations, $srcDisk, $targetDisk, $callback);
    }

}