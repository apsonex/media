<?php

namespace Apsonex\Media\Factory;

use Apsonex\Media\Factory\Concerns\InteractsWithOptimizer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Factory\Concerns\InteractWithTemporaryDirectory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Symfony\Component\Mime\MimeTypes;

class ImageOptimizer
{

    use InteractsWithOptimizer;
    use InteractWithTemporaryDirectory;

    protected Filesystem $srcDisk;
    protected string $from;
    protected ?string $to = null;
    protected ?Filesystem $targetDisk = null;
    protected bool $keepOriginal = false;
    protected ?string $tempDirLocation = null;
    protected string $driver = 'imagick';

    /**
     * Instantiate the class
     */
    public static function make(Filesystem $srcDisk, string $from, string $to = null, ?Filesystem $targetDisk = null, $keepOriginal = false): static
    {
        $self = new static();
        $self->srcDisk = $srcDisk;
        $self->from = $from;
        $self->to = $to ?: $from;
        $self->targetDisk = $targetDisk ?: $srcDisk;
        $self->keepOriginal = $keepOriginal;
        return $self;
    }


    public function optimize(): bool
    {
        $image = $this->makeImage($this->srcDisk->get($this->from));

        $tempDir = $this->temporaryDirectory();

        $tempPath = $tempDir->path(Str::uuid() . '.' . $this->extension($image));

        $image->save($tempPath);

        $this->getFreshOptimizer()->optimize($tempPath);

        $this->uploadToDisk($this->to, File::get($tempPath));

        $tempDir->delete();

        return true;
    }

    protected function makeImage($image): \Intervention\Image\Image
    {
        return (new ImageManager(['driver' => $this->driver]))->make($image);
    }

    protected function uploadToDisk($to, $img)
    {
        $this->targetDisk->put($to, $img);
    }

    /**
     * Get extension
     */
    protected function extension($image): string
    {
        return app(MimeTypes::class)->getExtensions($image->mime())[0] ?? 'jpg';
    }

    protected function getFreshOptimizer(): OptimizerChain
    {
        return \Spatie\LaravelImageOptimizer\OptimizerChainFactory::create($this->config());
    }

    protected function config(): array
    {
        return [
            /*
             * When calling `optimize` the package will automatically determine which optimizers
             * should run for the given image.
             */
            'optimizers'             => [

                Jpegoptim::class => [
                    '-m85', // set maximum quality to 85%
                    '--strip-all',  // this strips out all text information such as comments and EXIF data
                    '--all-progressive',  // this will make sure the resulting image is a progressive one
                ],

                Pngquant::class => [
                    '--force', // required parameter for this package
                ],

                Optipng::class => [
                    '-i0', // this will result in a non-interlaced, progressive scanned image
                    '-o2',  // this set the optimization level to two (multiple IDAT compression trials)
                    '-quiet', // required parameter for this package
                ],

                Svgo::class => [
                    '--disable=cleanupIDs', // disabling because it is know to cause troubles
                ],

                Gifsicle::class => [
                    '-b', // required parameter for this package
                    '-O3', // this produces the slowest but best results
                ],

                Cwebp::class => [
                    '-m 6', // for the slowest compression method in order to get the best compression.
                    '-pass 10', // for maximizing the amount of analysis pass.
                    '-mt', // multithreading for some speed improvements.
                    '-q 90', // quality factor that brings the least noticeable changes.
                ],
            ],

            /*
            * The directory where your binaries are stored.
            * Only use this when you binaries are not accessible in the global environment.
            */
            'binary_path'            => '',

            /*
             * The maximum time in seconds each optimizer is allowed to run separately.
             */
            'timeout'                => 60,

            /*
             * If set to `true` all output of the optimizer binaries will be appended to the default log.
             * You can also set this to a class that implements `Psr\Log\LoggerInterface`.
             */
            'log_optimizer_activity' => false,
        ];
    }

}