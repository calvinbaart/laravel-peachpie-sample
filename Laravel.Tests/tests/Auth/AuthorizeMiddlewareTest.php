<?php

namespace Illuminate\Tests\Auth;

use stdClass;
use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Gate;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthorizeMiddlewareTest extends TestCase
{
    protected $container;
    protected $user;
    protected $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new stdClass;

        Container::setInstance($this->container = new Container);

        $this->container->singleton(GateContract::class, function () {
            return new Gate($this->container, function () {
                return $this->user;
            });
        });

        $this->router = new Router(new Dispatcher, $this->container);

        $this->container->singleton(Registrar::class, function () {
            return $this->router;
        });
    }

    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    public function testSimpleAbilityUnauthorized()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $this->gate()->define('view-dashboard', function ($user, $additional = null) {
            $this->assertNull($additional);

            return false;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':view-dashboard',
            'uses' => function () {
                return 'success';
            },
        ]);

        $this->router->dispatch(Request::create('dashboard', 'GET'));
    }

    public function testSimpleAbilityAuthorized()
    {
        $this->gate()->define('view-dashboard', function ($user) {
            return true;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':view-dashboard',
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('dashboard', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testSimpleAbilityWithStringParameter()
    {
        $this->gate()->define('view-dashboard', function ($user, $param) {
            return $param === 'some string';
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':view-dashboard,"some string"',
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('dashboard', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testSimpleAbilityWithNullParameter()
    {
        $this->gate()->define('view-dashboard', function ($user, $param = null) {
            $this->assertNull($param);

            return true;
        });

        $this->router->get('dashboard', [
            'middleware' => Authorize::class.':view-dashboard,null',
            'uses' => function () {
                return 'success';
            },
        ]);

        $this->router->dispatch(Request::create('dashboard', 'GET'));
    }

    public function testSimpleAbilityWithOptionalParameter()
    {
        $post = new stdClass;

        $this->router->bind('post', function () use ($post) {
            return $post;
        });

        $this->gate()->define('view-comments', function ($user, $model = null) use ($post) {
            return true;
        });

        $middleware = [SubstituteBindings::class, Authorize::class.':view-comments,post'];

        $this->router->get('comments', [
            'middleware' => $middleware,
            'uses' => function () {
                return 'success';
            },
        ]);
        $this->router->get('posts/{post}/comments', [
            'middleware' => $middleware,
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('posts/1/comments', 'GET'));
        $this->assertEquals($response->content(), 'success');

        $response = $this->router->dispatch(Request::create('comments', 'GET'));
        $this->assertEquals($response->content(), 'success');
    }

    public function testSimpleAbilityWithStringParameterFromRouteParameter()
    {
        $this->gate()->define('view-dashboard', function ($user, $param) {
            return $param === 'true';
        });

        $this->router->get('dashboard/{route_parameter}', [
            'middleware' => Authorize::class.':view-dashboard,route_parameter',
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('dashboard/true', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testModelTypeUnauthorized()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $this->gate()->define('create', function ($user, $model) {
            $this->assertEquals($model, 'App\User');

            return false;
        });

        $this->router->get('users/create', [
            'middleware' => [SubstituteBindings::class, Authorize::class.':create,App\User'],
            'uses' => function () {
                return 'success';
            },
        ]);

        $this->router->dispatch(Request::create('users/create', 'GET'));
    }

    public function testModelTypeAuthorized()
    {
        $this->gate()->define('create', function ($user, $model) {
            $this->assertEquals($model, 'App\User');

            return true;
        });

        $this->router->get('users/create', [
            'middleware' => Authorize::class.':create,App\User',
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('users/create', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testModelUnauthorized()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $post = new stdClass;

        $this->router->bind('post', function () use ($post) {
            return $post;
        });

        $this->gate()->define('edit', function ($user, $model) use ($post) {
            $this->assertSame($model, $post);

            return false;
        });

        $this->router->get('posts/{post}/edit', [
            'middleware' => [SubstituteBindings::class, Authorize::class.':edit,post'],
            'uses' => function () {
                return 'success';
            },
        ]);

        $this->router->dispatch(Request::create('posts/1/edit', 'GET'));
    }

    public function testModelAuthorized()
    {
        $post = new stdClass;

        $this->router->bind('post', function () use ($post) {
            return $post;
        });

        $this->gate()->define('edit', function ($user, $model) use ($post) {
            $this->assertSame($model, $post);

            return true;
        });

        $this->router->get('posts/{post}/edit', [
            'middleware' => [SubstituteBindings::class, Authorize::class.':edit,post'],
            'uses' => function () {
                return 'success';
            },
        ]);

        $response = $this->router->dispatch(Request::create('posts/1/edit', 'GET'));

        $this->assertEquals($response->content(), 'success');
    }

    public function testModelInstanceAsParameter()
    {
        $instance = m::mock(Model::class);

        $this->gate()->define('success', function ($user, $model) use ($instance) {
            $this->assertSame($model, $instance);

            return true;
        });

        $request = m::mock(Request::class);

        $nextParam = null;

        $next = function ($param) use (&$nextParam) {
            $nextParam = $param;
        };

        (new Authorize($this->gate()))
            ->handle($request, $next, 'success', $instance);
    }

    /**
     * Get the Gate instance from the container.
     *
     * @return \Illuminate\Auth\Access\Gate
     */
    protected function gate()
    {
        return $this->container->make(GateContract::class);
    }
}
