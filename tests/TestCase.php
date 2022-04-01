<?php


namespace Apsonex\Media\Tests;


use Illuminate\Support\Facades\File;
use Apsonex\Media\MediaServiceProvider;
use Apsonex\Media\Tests\Concerns\Helpers;
use Apsonex\Media\Tests\Concerns\QueueUtils;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider;

class TestCase extends OrchestraTestCase
{
    use Helpers, QueueUtils;

    protected static string $tempDir = __DIR__ . '/temp';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            //'queue.default'                 => 'database', //'database'
            'filesystems.disks.local.root'  => realpath(__DIR__ . '/temp'),
            'filesystems.disks.public.root' => realpath(__DIR__ . '/temp/public'),
        ]);
    }


    protected function getPackageProviders($app): array
    {
        return [
            MediaServiceProvider::class,
            ImageOptimizerServiceProvider::class,
        ];
    }

    public function cleanTempDir(): void
    {
        foreach (File::directories(self::$tempDir) as $dir) {
            File::deleteDirectory($dir);
        }

        foreach (File::allFiles(self::$tempDir) as $allFile) {
            File::delete($allFile);
        }
    }

}
