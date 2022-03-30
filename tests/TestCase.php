<?php


namespace Apsonex\Media\Tests;


use Apsonex\Media\MediaServiceProvider;
use Apsonex\Media\Tests\Concerns\Helpers;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider;

class TestCase extends OrchestraTestCase
{

    use Helpers;

    protected static string $tempDir = __DIR__ . '/temp';

    protected function setUp(): void
    {
        parent::setUp();

        config([
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

}
