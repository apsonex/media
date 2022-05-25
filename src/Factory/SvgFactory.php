<?php

namespace Apsonex\Media\Factory;


use enshrined\svgSanitize\Sanitizer;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Illuminate\Support\Collection;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Apsonex\Media\Actions\ImageOptimizeAction;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Concerns\InteractsWithOptimizer;
use Apsonex\Media\Concerns\InteractsWithMimeTypes;
use Apsonex\Media\Actions\MakeImageVariationsAction;
use Apsonex\Media\Concerns\InteractWithTemporaryDirectory;

class SvgFactory
{
    use InteractsWithMimeTypes;


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

    protected string $svg;

    protected string $ext;


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
    public static function make(UploadedFile|string $svg): static
    {
        $self = (new static());
        $self->svg = is_string($svg) ? $svg : $svg->getContent();
        $self->ext = $svg instanceof UploadedFile ? $svg->guessClientExtension() : null;
        $self->sanitizeSvg();
        return $self;
    }

    public function sanitizeSvg()
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $this->svg = $sanitizer->sanitize($this->svg);
    }

    /**
     * Set target path
     */
    public function path(?string $path = null): static
    {
        $this->options['path'] = $path;
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
        $this->diskName = $disk;
        $this->disk = $disk ? Storage::disk($disk) : Storage::disk();
        return $this;
    }


    /**
     * Process request
     */
    public function process(): array
    {
        $this->configure();

        //$this->image->encode($this->extension(), $this->exportQuality);

        $this->options['variations']['original'] = $this->saveToDisk();

        return array_merge($this->baseAttributes(), [
            'variations' => $this->options['variations']
        ]);

        //$data = $this->optimizeIfRequested($data);

        //return $data;
    }

    /**
     * Save to disk
     */
    protected function saveToDisk(): array
    {
        $this->disk->put($this->options['path'], $this->svg, [
            'visibility' => $this->options['visibility'] === 'public' ? 'public' : 'private'
        ]);
        $this->options['size'] = (int)$this->disk->size($this->options['path']);

        return [
            'path'      => $this->options['path'],
            'filename'  => $this->options['filename'],
            'basename'  => $this->options['basename'],
            'extension' => $this->options['extension'],
            'mime'      => 'image/svg+xml',
            'size'      => $this->options['size'],
            'optimize'  => false,
        ];
    }

    /**
     * Configure paths, directory, basename, mime, extension
     */
    protected function configure()
    {
        $this->ensureValidPath();

        $this->options = array_merge($this->options, [
            'mime'      => $this->image->mime(),
            'extension' => $this->extension(),
            'type'      => 'image',
        ]);
    }

    protected function ensureValidPath()
    {
        $pathinfo = pathinfo(
            trim($this->options['path'] ??= $this->randomName(), '/')
        );

        $dirname = $pathinfo['dirname'] === '.' ? null : $pathinfo['dirname'];

        $this->options = array_merge($this->options, [
            'path'      => implode('/', array_filter([$dirname, $pathinfo['basename']])),
            'directory' => $dirname,
            'basename'  => $pathinfo['basename'], // name.extension
            'filename'  => $pathinfo['filename'], // name
            'extension' => $pathinfo['extension'],
        ]);

    }

    /**
     * Random name
     */
    protected function randomName(): string
    {
        return md5(Str::uuid()->toString()) . '.' . $this->extension();
    }

    /**
     * Get extension
     */
    protected function extension(): string
    {
        return $this->ext ?: 'svg';
    }


    protected function baseAttributes(): array
    {
        return [
            'disk'       => $this->diskName,
            'directory'  => $this->options['directory'],
            'visibility' => $this->options['visibility'],
            'type'       => $this->options['type'],
            'size'       => $this->options['size'],
            'mime'       => $this->options['mime'],
            'optimize'   => false,
        ];
    }
}
