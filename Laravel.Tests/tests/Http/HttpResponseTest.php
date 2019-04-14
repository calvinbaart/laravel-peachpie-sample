<?php

namespace Illuminate\Tests\Http;

use Mockery as m;
use JsonSerializable;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderBag;
use Illuminate\Contracts\Support\MessageProvider;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class HttpResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testJsonResponsesAreConvertedAndHeadersAreSet()
    {
        $response = new Response(new ArrayableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new JsonableStub);
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new ArrayableAndJsonableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new Response;
        $response->setContent(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new JsonSerializableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new ArrayableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response->setContent('{"foo": "bar"}');
        $this->assertEquals('{"foo": "bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function testRenderablesAreRendered()
    {
        $mock = m::mock(Renderable::class);
        $mock->shouldReceive('render')->once()->andReturn('foo');
        $response = new Response($mock);
        $this->assertEquals('foo', $response->getContent());
    }

    public function testHeader()
    {
        $response = new Response;
        $this->assertNull($response->headers->get('foo'));
        $response->header('foo', 'bar');
        $this->assertEquals('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz', false);
        $this->assertEquals('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz');
        $this->assertEquals('baz', $response->headers->get('foo'));
    }

    public function testWithCookie()
    {
        $response = new Response;
        $this->assertCount(0, $response->headers->getCookies());
        $this->assertEquals($response, $response->withCookie(new Cookie('foo', 'bar')));
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('foo', $cookies[0]->getName());
        $this->assertEquals('bar', $cookies[0]->getValue());
    }

    public function testGetOriginalContent()
    {
        $arr = ['foo' => 'bar'];
        $response = new Response;
        $response->setContent($arr);
        $this->assertSame($arr, $response->getOriginalContent());
    }

    public function testGetOriginalContentRetrievesTheFirstOriginalContent()
    {
        $previousResponse = new Response(['foo' => 'bar']);
        $response = new Response($previousResponse);

        $this->assertSame(['foo' => 'bar'], $response->getOriginalContent());
    }

    public function testSetAndRetrieveStatusCode()
    {
        $response = new Response('foo');
        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testOnlyInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $response->onlyInput('name');
    }

    public function testExceptInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $response->exceptInput('age');
    }

    public function testFlashingErrorsOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('get')->with('errors', m::type(ViewErrorBag::class))->andReturn(new ViewErrorBag);
        $session->shouldReceive('flash')->once()->with('errors', m::type(ViewErrorBag::class));
        $provider = m::mock(MessageProvider::class);
        $provider->shouldReceive('getMessageBag')->once()->andReturn(new MessageBag);
        $response->withErrors($provider);
    }

    public function testSettersGettersOnRequest()
    {
        $response = new RedirectResponse('foo.bar');
        $this->assertNull($response->getRequest());
        $this->assertNull($response->getSession());

        $request = Request::create('/', 'GET');
        $session = m::mock(Store::class);
        $response->setRequest($request);
        $response->setSession($session);
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($session, $response->getSession());
    }

    public function testRedirectWithErrorsArrayConvertsToMessageBag()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('get')->with('errors', m::type(ViewErrorBag::class))->andReturn(new ViewErrorBag);
        $session->shouldReceive('flash')->once()->with('errors', m::type(ViewErrorBag::class));
        $provider = ['foo' => 'bar'];
        $response->withErrors($provider);
    }

    public function testWithHeaders()
    {
        $response = new Response(null, 200, ['foo' => 'bar']);
        $this->assertSame('bar', $response->headers->get('foo'));

        $response->withHeaders(['foo' => 'BAR', 'bar' => 'baz']);
        $this->assertSame('BAR', $response->headers->get('foo'));
        $this->assertSame('baz', $response->headers->get('bar'));

        $responseMessageBag = new ResponseHeaderBag(['bar' => 'BAZ', 'titi' => 'toto']);
        $response->withHeaders($responseMessageBag);
        $this->assertSame('BAZ', $response->headers->get('bar'));
        $this->assertSame('toto', $response->headers->get('titi'));

        $headerBag = new HeaderBag(['bar' => 'BAAA', 'titi' => 'TATA']);
        $response->withHeaders($headerBag);
        $this->assertSame('BAAA', $response->headers->get('bar'));
        $this->assertSame('TATA', $response->headers->get('titi'));
    }

    public function testMagicCall()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flash')->once()->with('foo', 'bar');
        $response->withFoo('bar');
    }

    public function testMagicCallException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method Illuminate\Http\RedirectResponse::doesNotExist()');

        $response = new RedirectResponse('foo.bar');
        $response->doesNotExist('bar');
    }
}

class ArrayableStub implements Arrayable
{
    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}

class ArrayableAndJsonableStub implements Arrayable, Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }

    public function toArray()
    {
        return [];
    }
}

class JsonableStub implements Jsonable
{
    public function toJson($options = 0)
    {
        return 'foo';
    }
}

class JsonSerializableStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'bar'];
    }
}
