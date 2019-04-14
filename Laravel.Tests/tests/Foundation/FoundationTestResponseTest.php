<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use JsonSerializable;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\AssertionFailedError;
use Illuminate\Foundation\Testing\TestResponse;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FoundationTestResponseTest extends TestCase
{
    public function testAssertViewIs()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'getData' => ['foo' => 'bar'],
            'name' => 'dir.my-view',
        ]);

        $response->assertViewIs('dir.my-view');
    }

    public function testAssertViewHas()
    {
        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'getData' => ['foo' => 'bar'],
        ]);

        $response->assertViewHas('foo');
    }

    public function testAssertViewHasModel()
    {
        $model = new class extends Model {
            public function is($model)
            {
                return $this == $model;
            }
        };

        $response = $this->makeMockResponse([
            'render' => 'hello world',
            'getData' => ['foo' => $model],
        ]);

        $response->original->foo = $model;

        $response->assertViewHas('foo', $model);
    }

    public function testAssertSeeInOrder()
    {
        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSeeInOrder(['foo', 'bar', 'baz']);

        $response->assertSeeInOrder(['foo', 'bar', 'baz', 'foo']);
    }

    public function testAssertSeeInOrderCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSeeInOrder(['baz', 'bar', 'foo']);
    }

    public function testAssertSeeInOrderCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => '<ul><li>foo</li><li>bar</li><li>baz</li><li>foo</li></ul>',
        ]);

        $response->assertSeeInOrder(['foo', 'qux', 'bar', 'baz']);
    }

    public function testAssertSeeText()
    {
        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong>',
        ]);

        $response->assertSeeText('foobar');
    }

    public function testAssertSeeTextInOrder()
    {
        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong> baz <strong>foo</strong>',
        ]);

        $response->assertSeeTextInOrder(['foobar', 'baz']);

        $response->assertSeeTextInOrder(['foobar', 'baz', 'foo']);
    }

    public function testAssertSeeTextInOrderCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong> baz <strong>foo</strong>',
        ]);

        $response->assertSeeTextInOrder(['baz', 'foobar']);
    }

    public function testAssertSeeTextInOrderCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeMockResponse([
            'render' => 'foo<strong>bar</strong> baz <strong>foo</strong>',
        ]);

        $response->assertSeeTextInOrder(['foobar', 'qux', 'baz']);
    }

    public function testAssertHeader()
    {
        $this->expectException(AssertionFailedError::class);

        $baseResponse = tap(new Response, function ($response) {
            $response->header('Location', '/foo');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertHeader('Location', '/bar');
    }

    public function testAssertHeaderMissing()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Unexpected header [Location] is present on response.');

        $baseResponse = tap(new Response, function ($response) {
            $response->header('Location', '/foo');
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertHeaderMissing('Location');
    }

    public function testAssertJsonWithArray()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $resource = new JsonSerializableSingleResourceStub;

        $response->assertJson($resource->jsonSerialize());
    }

    public function testAssertJsonWithNull()
    {
        $response = TestResponse::fromBaseResponse(new Response(null));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Invalid JSON was returned from the route.');

        $resource = new JsonSerializableSingleResourceStub;

        $response->assertJson($resource->jsonSerialize());
    }

    public function testAssertJsonWithMixed()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $resource = new JsonSerializableMixedResourcesStub;

        $response->assertExactJson($resource->jsonSerialize());
    }

    public function testAssertJsonFragment()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJsonFragment(['foo' => 'foo 0']);

        $response->assertJsonFragment(['foo' => 'foo 0', 'bar' => 'bar 0', 'foobar' => 'foobar 0']);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $response->assertJsonFragment(['foo' => 'bar']);

        $response->assertJsonFragment(['foobar_foo' => 'foo']);

        $response->assertJsonFragment(['foobar' => ['foobar_foo' => 'foo', 'foobar_bar' => 'bar']]);

        $response->assertJsonFragment(['foo' => 'bar 0', 'bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']]);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonFragment(['id' => 10]);
    }

    public function testAssertJsonFragmentCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonFragment(['id' => 1]);
    }

    public function testAssertJsonStructure()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        // Without structure
        $response->assertJsonStructure();

        // At root
        $response->assertJsonStructure(['foo']);

        // Nested
        $response->assertJsonStructure(['foobar' => ['foobar_foo', 'foobar_bar']]);

        // Wildcard (repeating structure)
        $response->assertJsonStructure(['bars' => ['*' => ['bar', 'foo']]]);

        // Wildcard (numeric keys)
        $response->assertJsonStructure(['numeric_keys' => ['*' => ['bar', 'foo']]]);

        // Nested after wildcard
        $response->assertJsonStructure(['baz' => ['*' => ['foo', 'bar' => ['foo', 'bar']]]]);

        // Wildcard (repeating structure) at root
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));

        $response->assertJsonStructure(['*' => ['foo', 'bar', 'foobar']]);
    }

    public function testAssertJsonCount()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        // With simple key
        $response->assertJsonCount(3, 'bars');

        // With nested key
        $response->assertJsonCount(1, 'barfoo.0.bar');
        $response->assertJsonCount(3, 'barfoo.2.bar');

        // Without structure
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceStub));
        $response->assertJsonCount(4);
    }

    public function testAssertJsonMissing()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissing(['id' => 20]);
    }

    public function testAssertJsonMissingExact()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissingExact(['id' => 2]);

        // This is missing because bar has changed to baz
        $response->assertJsonMissingExact(['id' => 20, 'foo' => 'baz']);
    }

    public function testAssertJsonMissingExactCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissingExact(['id' => 20]);
    }

    public function testAssertJsonMissingExactCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableSingleResourceWithIntegersStub));

        $response->assertJsonMissingExact(['id' => 20, 'foo' => 'bar']);
    }

    public function testAssertJsonValidationErrors()
    {
        $data = [
            'status' => 'ok',
            'errors' => ['foo' => 'oops'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('foo');
    }

    public function testAssertJsonValidationErrorsCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = [
            'status' => 'ok',
            'errors' => ['foo' => 'oops'],
        ];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('bar');
    }

    public function testAssertJsonValidationErrorsCanFailWhenThereAreNoErrors()
    {
        $this->expectException(AssertionFailedError::class);

        $data = ['status' => 'ok'];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonValidationErrors('bar');
    }

    public function testAssertJsonValidationErrorsFailsWhenGivenAnEmptyArray()
    {
        $this->expectException(AssertionFailedError::class);

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode(['errors' => ['foo' => 'oops']]))
        );

        $testResponse->assertJsonValidationErrors([]);
    }

    public function testAssertJsonMissingValidationErrors()
    {
        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['errors' => [
                    'foo' => [],
                    'bar' => ['one', 'two'],
                ]]
            ));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertJsonMissingValidationErrors('baz');

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['foo' => 'bar']));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);
        $response->assertJsonMissingValidationErrors('foo');
    }

    public function testAssertJsonMissingValidationErrorsCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['errors' => [
                    'foo' => [],
                    'bar' => ['one', 'two'],
                ]]
            ));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertJsonMissingValidationErrors('foo');
    }

    public function testAssertJsonMissingValidationErrorsCanFail2()
    {
        $this->expectException(AssertionFailedError::class);

        $baseResponse = tap(new Response, function ($response) {
            $response->setContent(json_encode(['errors' => [
                    'foo' => [],
                    'bar' => ['one', 'two'],
                ]]
            ));
        });

        $response = TestResponse::fromBaseResponse($baseResponse);

        $response->assertJsonMissingValidationErrors('bar');
    }

    public function testAssertJsonMissingValidationErrorsWithoutArgument()
    {
        $data = ['status' => 'ok'];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors();
    }

    public function testAssertJsonMissingValidationErrorsWithoutArgumentWhenErrorsIsEmpty()
    {
        $data = ['status' => 'ok', 'errors' => []];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors();
    }

    public function testAssertJsonMissingValidationErrorsWithoutArgumentCanFail()
    {
        $this->expectException(AssertionFailedError::class);

        $data = ['errors' => ['foo' => []]];

        $testResponse = TestResponse::fromBaseResponse(
            (new Response)->setContent(json_encode($data))
        );

        $testResponse->assertJsonMissingValidationErrors();
    }

    public function testMacroable()
    {
        TestResponse::macro('foo', function () {
            return 'bar';
        });

        $response = TestResponse::fromBaseResponse(new Response);

        $this->assertEquals(
            'bar', $response->foo()
        );
    }

    public function testCanBeCreatedFromBinaryFileResponses()
    {
        $files = new Filesystem;
        $tempDir = __DIR__.'/tmp';
        $files->makeDirectory($tempDir, 0755, false, true);
        $files->put($tempDir.'/file.txt', 'Hello World');

        $response = TestResponse::fromBaseResponse(new BinaryFileResponse($tempDir.'/file.txt'));

        $this->assertEquals($tempDir.'/file.txt', $response->getFile()->getPathname());

        $files->deleteDirectory($tempDir);
    }

    public function testJsonHelper()
    {
        $response = TestResponse::fromBaseResponse(new Response(new JsonSerializableMixedResourcesStub));

        $this->assertEquals('foo', $response->json('foobar.foobar_foo'));
        $this->assertEquals(
            json_decode($response->getContent(), true),
            $response->json()
        );
    }

    private function makeMockResponse($content)
    {
        $baseResponse = tap(new Response, function ($response) use ($content) {
            $response->setContent(m::mock(View::class, $content));
        });

        return TestResponse::fromBaseResponse($baseResponse);
    }
}

class JsonSerializableMixedResourcesStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'foo' => 'bar',
            'foobar' => [
                'foobar_foo' => 'foo',
                'foobar_bar' => 'bar',
            ],
            'bars' => [
                ['bar' => 'foo 0', 'foo' => 'bar 0'],
                ['bar' => 'foo 1', 'foo' => 'bar 1'],
                ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
            'baz' => [
                ['foo' => 'bar 0', 'bar' => ['foo' => 'bar 0', 'bar' => 'foo 0']],
                ['foo' => 'bar 1', 'bar' => ['foo' => 'bar 1', 'bar' => 'foo 1']],
            ],
            'barfoo' => [
                ['bar' => ['bar' => 'foo 0']],
                ['bar' => ['bar' => 'foo 0', 'bar' => 'foo 0']],
                ['bar' => ['foo' => 'bar 0', 'bar' => 'foo 0', 'rab' => 'rab 0']],
            ],
            'numeric_keys' => [
                2 => ['bar' => 'foo 0', 'foo' => 'bar 0'],
                3 => ['bar' => 'foo 1', 'foo' => 'bar 1'],
                4 => ['bar' => 'foo 2', 'foo' => 'bar 2'],
            ],
        ];
    }
}

class JsonSerializableSingleResourceStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            ['foo' => 'foo 0', 'bar' => 'bar 0', 'foobar' => 'foobar 0'],
            ['foo' => 'foo 1', 'bar' => 'bar 1', 'foobar' => 'foobar 1'],
            ['foo' => 'foo 2', 'bar' => 'bar 2', 'foobar' => 'foobar 2'],
            ['foo' => 'foo 3', 'bar' => 'bar 3', 'foobar' => 'foobar 3'],
        ];
    }
}

class JsonSerializableSingleResourceWithIntegersStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            ['id' => 10, 'foo' => 'bar'],
            ['id' => 20, 'foo' => 'bar'],
            ['id' => 30, 'foo' => 'bar'],
        ];
    }
}
