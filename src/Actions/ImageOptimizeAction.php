<?php

namespace Apsonex\Media\Actions;

use Illuminate\Support\Str;
use Intervention\Image\Image;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Illuminate\Contracts\Filesystem\Filesystem;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Apsonex\Media\Concerns\HasSerializedCallback;
use Apsonex\Media\Concerns\InteractsWithMimeTypes;
use Apsonex\Media\Concerns\InteractsWithOptimizer;
use Spatie\LaravelImageOptimizer\OptimizerChainFactory;
use Apsonex\Media\Concerns\InteractWithTemporaryDirectory;

/**
 * https://github.com/psliwa/image-optimizer
 */
class ImageOptimizeAction
{

    use InteractsWithOptimizer;
    use InteractsWithMimeTypes;
    use InteractWithTemporaryDirectory;
    use HasSerializedCallback;

    protected string $from;
    protected ?int $quality;
    protected ?Image $image;
    protected string $tempPath;
    protected int $originalSize;
    protected int $optimizedSize;
    protected ?string $to = null;
    protected ?string $extension;
    protected string $visibility;
    protected Filesystem $srcDisk;
    protected string $driver = 'imagick';
    protected TemporaryDirectory $tempDir;
    protected ?Filesystem $targetDisk = null;
    protected ?string $tempDirLocation = null;

    /**
     * Callback called when finished
     */
    protected mixed $onFinishCallback = null;

    /**
     * Queue self
     */
    public static function queue(string $srcDisk, string $from, string $to = null, string $targetDisk = null, $quality = 85, mixed $onFinishCallback = null, $onQueue = 'default')
    {
        dispatch(function () use ($srcDisk, $from, $to, $targetDisk, $quality, $onFinishCallback) {
            ImageOptimizeAction::make(
                srcDisk: $srcDisk,
                from: $from,
                to: $to,
                targetDisk: $targetDisk,
                quality: $quality,
                onFinishCallback: $onFinishCallback,
            )->optimize();
        })->onQueue($onQueue);
    }

    /**
     * Instantiate the class
     */
    public static function make(string $srcDisk, string $from, string $to = null, string $targetDisk = null, $quality = 85, mixed $onFinishCallback = null): static
    {
        $self = new static();
        $self->srcDisk = Storage::disk($srcDisk);
        $self->targetDisk = $targetDisk ? Storage::disk($targetDisk) : Storage::disk($srcDisk);
        $self->from = $from;
        $self->to = $to ?: $from;
        $self->quality = ($quality > 0 && $quality < 100) ? $quality : null;
        $self->onFinishCallback = $self->serializeCallback($onFinishCallback);
        return $self;
    }

    /**
     * Optimize
     */
    public function optimize(): bool
    {
        $this->makeInterventionImage();

        $this->createTempDir();

        $this->optimizeInTempDir();

        $this->uploadIfOptimized();

        $this->cleanTempDir();

        $this->triggerOnFinishCallback();

        return true;
    }

    /**
     * Make Intervention Image Instance
     */
    protected function makeInterventionImage()
    {
        //$image = $this->srcDisk->get($this->from);
        if (!$image = $this->getFile()) return;

        $this->image = (new ImageManager(['driver' => $this->driver]))->make($image);

        $this->originalSize = $this->image->filesize() ?: 0;

        $this->visibility = $this->srcDisk->getVisibility($this->from);

        $this->extension = static::guessExtensionFromMimeType($this->image->mime());

        $image = null;
    }

    protected function getFile()
    {
        try {
            return $this->srcDisk->get($this->from);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upload Disk
     */
    protected function uploadToDisk()
    {
        ($this->targetDisk ?: $this->srcDisk)->put($this->to, File::get($this->tempPath), [
            'visibility' => $this->visibility == 'public' ? 'public' : 'private'
        ]);
    }

    /**
     * Get fresh optimizer
     */
    protected function getFreshOptimizer(): OptimizerChain
    {
        return OptimizerChainFactory::create($this->config());
    }

    /**
     * Create Temporary Directory
     */
    protected function createTempDir()
    {
        $this->tempDir = $this->temporaryDirectory();

        $this->tempPath = $this->tempDir->path(Str::uuid() . '.' . $this->extension);
    }

    /**
     * Optimize in temporary directory
     */
    protected function optimizeInTempDir()
    {
        $this->image->save($this->tempPath);

        $this->getFreshOptimizer()->optimize($this->tempPath);

        $this->optimizedSize = File::size($this->tempPath);
    }

    /**
     * Check if optimized
     */
    protected function optimized(): bool
    {
        //        Log::debug('------');
        //
        //        $data = [
        //            'OptimizedSize' => $this->optimizedSize,
        //            'OriginalSize'  => $this->originalSize,
        //            'Optimized'     => ($this->optimizedSize < $this->originalSize ? 'true' : 'false'),
        //            'ID'            => str($this->from)->afterLast('/')->toString(),
        //            'DiskConfig'    => $this->srcDisk->getConfig(),
        //        ];
        //
        //        Log::debug(json_encode($data));
        //
        //        Log::debug('------');

        return $this->optimizedSize < $this->originalSize;
    }

    /**
     * Upload if optimized
     */
    protected function uploadIfOptimized()
    {
        if (!$this->optimized()) return;

        $this->uploadToDisk();
    }

    /**
     * Clean temporary directory
     */
    protected function cleanTempDir()
    {
        $this->tempDir->delete();
    }

    /**
     * Trigger onFinishCallback
     */
    protected function triggerOnFinishCallback()
    {
        $data = [
            'originalSize'    => $this->originalSize,
            'optimizedSize'   => $this->optimizedSize,
            'diskSpaceSaving' => $this->originalSize > $this->optimizedSize ? $this->originalSize - $this->optimizedSize : 0,
            'optimized'       => $this->optimized(),
            'from'            => $this->from,
            'to'              => $this->to,
            'srcDisk'         => $this->srcDisk->getConfig()['driver'],
            'targetDisk'      => $this->targetDisk->getConfig()['driver'],
        ];


        $this->triggerCallback($this->onFinishCallback, $data);
    }


    /**
     * Optimizer config
     */
    protected function config(): array
    {
        return [
            /*
             * When calling `optimize` the package will automatically determine which optimizers
             * should run for the given image.
             */
            'optimizers'             => [

                Jpegoptim::class => [
                    '-m' . ($this->quality ?: '75'), // set maximum quality to 85%
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
