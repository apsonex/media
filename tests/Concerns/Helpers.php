<?php

namespace Apsonex\Media\Tests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Mime\MimeTypes;

trait Helpers
{

    protected function emptyTempDirectory()
    {
        foreach (File::directories(self::$tempDir) as $dir) {
            File::deleteDirectory($dir);
        }

        foreach (File::allFiles(self::$tempDir) as $allFile) {
            File::delete($allFile);
        }
    }

    protected function assertDecreasedFileSize(string $modifiedFilePath, string $originalFilePath)
    {
        $this->assertFileExists($originalFilePath);

        $this->assertFileExists($modifiedFilePath);

        $originalFileSize = filesize($originalFilePath);

        $modifiedFileSize = filesize($modifiedFilePath);

        $this->assertTrue(
            $modifiedFileSize < $originalFileSize,
            "File {$modifiedFilePath} as size {$modifiedFileSize} which is not less than {$originalFileSize}"
        );

        $this->assertTrue($modifiedFileSize > 0, "File {$modifiedFilePath} had a filesize of zero. Something must have gone wrong...");
    }

    protected function processAllJobs($callback = null)
    {
        $jobs = $this->allJobs();

        if ($callback) {
            $jobs->each(fn($job) => $callback($job));
        } else {
            $jobs->each(function ($job) {
                $jobClass = unserialize(json_decode($job->payload, true)['data']['command']);
                $jobClass->handle();
            });
        }
    }

    protected function allJobs(): Collection
    {
        return DB::table('jobs')->get();
    }

    /**
     * @param string $name
     * @return UploadedFile
     */
    protected function testMedia(string $name): UploadedFile
    {
        $mime = (new MimeTypes)->getMimeTypes(pathinfo($name, PATHINFO_EXTENSION))[0];

        return (new UploadedFile(__DIR__ . '/../fixtures/' . $name, $name, $mime, true));
    }

}