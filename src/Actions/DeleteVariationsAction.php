<?php

namespace Apsonex\Media\Actions;


use Closure;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Apsonex\Media\Concerns\HasSerializedCallback;

class DeleteVariationsAction
{

    use HasSerializedCallback;

    /**
     * Disk
     */
    protected Filesystem $disk;

    protected mixed $callback = null;

    /**
     * Constructor
     */
    public function __construct(
        protected string $diskDriver,
        protected array  $variations,
    ) {
        $this->configureDisk();
    }

    /**
     * Dispatch to queue
     */
    public static function queue(string $diskDriver, array $variations, mixed $callback = null, $queueName = 'default')
    {
        dispatch(function () use ($diskDriver, $variations, $callback) {
            DeleteVariationsAction::execute($diskDriver, $variations, $callback);
        })->onQueue($queueName);
    }

    /**
     * Execute
     */
    public static function execute($diskDriver, array $variations, mixed $callback = null): bool
    {
        try {
            $self = new static($diskDriver, $variations);
            $self->callback = $self->serializeCallback($callback);
            $self->process();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Actual Execution
     */
    protected function process()
    {
        $paths = [];

        foreach ($this->variations as $nameOrIndex => $path) {
            $pathToDelete = is_array($path) ? Arr::get($path, 'path', null) : $path;

            if (blank($pathToDelete) && !is_string($pathToDelete)) continue;

            try {
                $this->disk->delete($pathToDelete);
                $paths[] = $pathToDelete;
            } catch (Exception $e) {
                //
            }
        }

        $this->deleteDirectoriesIfEmpty($paths);

        $this->triggerCallback($this->callback);
    }

    /**
     * Delete Directory if empty
     */
    protected function deleteDirectoriesIfEmpty(array $paths)
    {
        collect($paths)
            ->map(fn ($p) => pathinfo($p)['dirname'] ?? null)
            ->filter()
            ->filter(fn ($p) => $p !== '.')
            ->unique()
            ->map($this->safelyDeleteDirectory());
    }

    /**
     * Delete directory only if empty
     */
    protected function safelyDeleteDirectory(): Closure
    {
        return function ($dir) {
            $files = $this->disk->files($dir);
            $dirs = $this->disk->directories($dir);
            if (empty($files) && empty($dirs)) {
                $this->disk->deleteDirectory($dir);
            }
        };
    }

    /**
     * Configure disk
     */
    protected function configureDisk()
    {
        $this->disk = Storage::disk($this->diskDriver);
    }
}
