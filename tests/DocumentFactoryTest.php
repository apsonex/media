<?php

namespace Apsonex\Media\Tests;

use Apsonex\Media\Facades\Media;
use Illuminate\Support\Facades\File;

class DocumentFactoryTest extends TestCase
{
    /** @test */
    public function it_store_documents_to_disk()
    {
        $this->cleanTempDir();

        $uploadedFile = $this->testMedia('document.pdf');

        $data = Media::documentFactory($uploadedFile, 'some/path/test-file.pdf', 'local')->visibilityPublic()->process();

        $this->assertCount(1, File::allFiles(static::$tempDir . '/some/path'));

        $image = File::allFiles(static::$tempDir . '/some/path')[0];

        $this->assertEquals('test-file.pdf', $image->getFilename());

        $this->cleanTempDir();
    }

    /** @test */
    public function document_can_be_store_without_path_and_disk()
    {
        $this->cleanTempDir();

        $this->assertCount(0, File::allFiles(static::$tempDir));

        $uploadedFile = $this->testMedia('document.pdf');

        Media::documentFactory($uploadedFile)->process();

        $this->assertCount(1, File::allFiles(static::$tempDir));

        $this->cleanTempDir();
    }
}