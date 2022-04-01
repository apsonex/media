<?php

namespace Apsonex\Media\Factory;

use Apsonex\Media\Actions\ImageOptimizeAction;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Image;

class Media
{

    public function putImage(Image|UploadedFile|string $src, ?string $targetPath = null, ?Filesystem $disk = null): ImageFactory
    {
        return ImageFactory::make($src)->path($targetPath)->storageDisk($disk);
    }


    public function imageOptimizer(Filesystem $srcDisk, string $srcPath, string $srcTarget = null, ?Filesystem $targetDisk = null, $keepOriginal = false): ImageOptimizeAction
    {
        return ImageOptimizeAction::make($srcDisk, $srcPath, $srcTarget, $targetDisk, $keepOriginal);
    }

}