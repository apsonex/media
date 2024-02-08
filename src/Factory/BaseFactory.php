<?php

namespace Apsonex\Media\Factory;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Concerns\InteractsWithMimeTypes;
use Illuminate\Support\Str;


abstract class BaseFactory
{
    use InteractsWithMimeTypes;

    protected array $optimizationOptions = [
        'quality'         => 85,
        'onFinishTrigger' => null,
    ];

    protected bool|string $triggerOptimization = false;

    protected int $exportQuality = 75;

    /**
     * Factory options
     */
    public array $options = [
        'disk'       => null,
        'size'       => null,
        'mime'       => null,
        'visibility' => 'private',
        'directory'  => null,
        'filename'   => null,
        'basename'   => null,
        'extension'  => null,
        'optimized'  => false,
        'variations' => [
            'original' => [
                'path'      => null,
                'filename'  => null,
                'basename'  => null,
                'extension' => null,
                'optimized' => false,
                'mime'      => null,
                'size'      => null,
                'width'     => null,
                'height'    => null,
            ]
        ],
    ];

    /**
     * Full target path of the images.
     * If not provided we will make one.
     */
    public ?string $targetPath;

    /**
     * Storage Disk
     */
    public Filesystem $disk;

    public string $diskName;

    public string $ext;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->disk = Storage::disk();

        $this->targetPath = null;
    }

    /**
     * Make instance of static
     */
    public static function make($src, $options = []): static
    {
        return (new static())->mergeOptions($options)->source($src);
    }

    /**
     * Merge options
     */
    public function mergeOptions(array $options = []): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Random name
     */
    protected function randomName(): string
    {
        return md5(Str::uuid()->toString()) . '.' . $this->ext;
    }

    /**
     * Set target path
     */
    public function path(?string $path = null): static
    {
        $this->options['path'] = $path ?: $this->options['path'];
        return $this;
    }

    /**
     * Mark visibility public
     */
    public function visibilityPublic(): static
    {
        $this->options['visibility'] = 'public';
        return $this;
    }

    /**
     * Set Storage Disk
     */
    public function storageDisk(string $disk = null): static
    {
        $this->diskName = $disk ?: 'local';
        $this->disk = $disk ? Storage::disk($this->diskName) : Storage::disk('local');
        return $this;
    }

    /**
     * Configure to do optimization
     */
    public function optimize($optimizationOptions = []): static
    {
        $this->triggerOptimization = true;
        $this->optimizationOptions = array_merge($this->optimizationOptions, $optimizationOptions);
        return $this;
    }

    public function queueOptimization($optimizationOptions = []): static
    {
        $this->triggerOptimization = 'queue';
        $this->optimizationOptions = array_merge($this->optimizationOptions, $optimizationOptions);
        return $this;
    }

    protected function ensureValidPath(): void
    {
        $pathInfo = pathinfo(
            trim($this->options['path'] ??= $this->randomName(), '/')
        );

        $dirname = $pathInfo['dirname'] === '.' ? null : $pathInfo['dirname'];

        $this->options = array_merge($this->options, [
            'path'      => implode('/', array_filter([$dirname, $pathInfo['basename']])),
            'directory' => $dirname,
            'basename'  => $pathInfo['basename'],
            'filename'  => $pathInfo['filename'],
            'extension' => $pathInfo['extension'],
        ]);
    }
}
