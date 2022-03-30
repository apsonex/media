<?php

namespace Apsonex\Media\Factory;

use Apsonex\Media\Factory\Results\ImageResult;
use Intervention\Image\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Filesystem\Filesystem;

class Media
{

    public function putImage(Image|UploadedFile|string $src, ?string $targetPath = null, ?Filesystem $disk = null): ImageFactory
    {
        return ImageFactory::make($src)->path($targetPath)->storageDisk($disk);
    }


    public function imageOptimizer(Filesystem $srcDisk, string $srcPath, string $srcTarget = null, ?Filesystem $targetDisk = null, $keepOriginal = false): ImageOptimizer
    {
        return ImageOptimizer::make($srcDisk, $srcPath, $srcTarget, $targetDisk, $keepOriginal);
    }

}