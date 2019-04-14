<?php

namespace Illuminate\Tests\Http;

use PHPUnit\Framework\TestCase;
use Illuminate\Http\Testing\MimeType;

class HttpMimeTypeTest extends TestCase
{
    public function testMimeTypeFromFileNameExistsTrue()
    {
        $this->assertSame('image/jpeg', MimeType::from('foo.jpg'));
    }

    public function testMimeTypeFromFileNameExistsFalse()
    {
        $this->assertSame('application/octet-stream', MimeType::from('foo.bar'));
    }

    public function testMimeTypeFromExtensionExistsTrue()
    {
        $this->assertSame('image/jpeg', MimeType::get('jpg'));
    }

    public function testMimeTypeFromExtensionExistsFalse()
    {
        $this->assertSame('application/octet-stream', MimeType::get('bar'));
    }

    public function testGetAllMimeTypes()
    {
        $this->assertIsArray(MimeType::get());
        $this->assertArrayHasKey('jpg', MimeType::get());
        $this->assertEquals('image/jpeg', MimeType::get()['jpg']);
    }

    public function testSearchExtensionFromMimeType()
    {
        $this->assertSame('mov', MimeType::search('video/quicktime'));
        $this->assertNull(MimeType::search('foo/bar'));
    }
}
