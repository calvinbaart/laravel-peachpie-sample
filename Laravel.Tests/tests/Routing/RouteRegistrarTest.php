<?php

namespace Illuminate\Tests\Routing;

use Mockery as m;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class RouteRegistrarTest extends TestCase
{
    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new Router(m::mock(Dispatcher::class), Container::getInstance());
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testMiddlewareFluentRegistration()
    {
        $this->router->middleware(['one', 'two'])->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['one', 'two'], $this->getRoute()->middleware());

        $this->router->middleware('three', 'four')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['three', 'four'], $this->getRoute()->middleware());

        $this->router->get('users', function () {
            return 'all-users';
        })->middleware('five', 'six');

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['five', 'six'], $this->getRoute()->middleware());

        $this->router->middleware('seven')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['seven'], $this->getRoute()->middleware());
    }

    public function testCanRegisterGetRouteWithClosureAction()
    {
        $this->router->middleware('get-middleware')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->seeMiddleware('get-middleware');
    }

    public function testCanRegisterPostRouteWithClosureAction()
    {
        $this->router->middleware('post-middleware')->post('users', function () {
            return 'saved';
        });

        $this->seeResponse('saved', Request::create('users', 'POST'));
        $this->seeMiddleware('post-middleware');
    }

    public function testCanRegisterAnyRouteWithClosureAction()
    {
        $this->router->middleware('test-middleware')->any('users', function () {
            return 'anything';
        });

        $this->seeResponse('anything', Request::create('users', 'PUT'));
        $this->seeMiddleware('test-middleware');
    }

    public function testCanRegisterMatchRouteWithClosureAction()
    {
        $this->router->middleware('match-middleware')->match(['DELETE'], 'users', function () {
            return 'deleted';
        });

        $this->seeResponse('deleted', Request::create('users', 'DELETE'));
        $this->seeMiddleware('match-middleware');
    }

    public function testCanRegisterRouteWithArrayAndClosureAction()
    {
        $this->router->middleware('patch-middleware')->patch('users', [function () {
            return 'updated';
        }]);

        $this->seeResponse('updated', Request::create('users', 'PATCH'));
        $this->seeMiddleware('patch-middleware');
    }

    public function testCanRegisterRouteWithArrayAndClosureUsesAction()
    {
        $this->router->middleware('put-middleware')->put('users', ['uses' => function () {
            return 'replaced';
        }]);

        $this->seeResponse('replaced', Request::create('users', 'PUT'));
        $this->seeMiddleware('put-middleware');
    }

    public function testCanRegisterRouteWithControllerAction()
    {
        $this->router->middleware('controller-middleware')
                     ->get('users', RouteRegistrarControllerStub::class.'@index');

        $this->seeResponse('controller', Request::create('users', 'GET'));
        $this->seeMiddleware('controller-middleware');
    }

    public function testCanRegisterRouteWithArrayAndControllerAction()
    {
        $this->router->middleware('controller-middleware')->put('users', [
            'uses' => RouteRegistrarControllerStub::class.'@index',
        ]);

        $this->seeResponse('controller', Request::create('users', 'PUT'));
        $this->seeMiddleware('controller-middleware');
    }

    public function testCanRegisterGroupWithMiddleware()
    {
        $this->router->middleware('group-middleware')->group(function ($router) {
            $router->get('users', function () {
                return 'all-users';
            });
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->seeMiddleware('group-middleware');
    }

    public function testCanRegisterGroupWithNamespace()
    {
        $this->router->namespace('App\Http\Controllers')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertEquals(
            'App\Http\Controllers\UsersController@index',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testCanRegisterGroupWithPrefix()
    {
        $this->router->prefix('api')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertEquals('api/users', $this->getRoute()->uri());
    }

    public function testCanRegisterGroupWithPrefixAndWhere()
    {
        $this->router->prefix('foo/{bar}')->where(['bar' => '[0-9]+'])->group(function ($router) {
            $router->get('here', function () {
                return 'good';
            });
        });

        $this->seeResponse('good', Request::create('foo/12345/here', 'GET'));
    }

    public function testCanRegisterGroupWithNamePrefix()
    {
        $this->router->name('api.')->group(function ($router) {
            $router->get('users', 'UsersController@index')->name('users');
        });

        $this->assertEquals('api.users', $this->getRoute()->getName());
    }

    public function testCanRegisterGroupWithDomain()
    {
        $this->router->domain('{account}.myapp.com')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertEquals('{account}.myapp.com', $this->getRoute()->getDomain());
    }

    public function testCanRegisterGroupWithDomainAndNamePrefix()
    {
        $this->router->domain('{account}.myapp.com')->name('api.')->group(function ($router) {
            $router->get('users', 'UsersController@index')->name('users');
        });

        $this->assertEquals('{account}.myapp.com', $this->getRoute()->getDomain());
        $this->assertEquals('api.users', $this->getRoute()->getName());
    }

    public function testRegisteringNonApprovedAttributesThrows()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method Illuminate\Routing\RouteRegistrar::missing does not exist.');

        $this->router->domain('foo')->missing('bar')->group(function ($router) {
            //
        });
    }

    public function testCanRegisterResource()
    {
        $this->router->middleware('resource-middleware')
                     ->resource('users', RouteRegistrarControllerStub::class);

        $this->seeResponse('deleted', Request::create('users/1', 'DELETE'));
        $this->seeMiddleware('resource-middleware');
    }

    public function testCanRegisterResourcesWithExceptOption()
    {
        $this->router->resources([
            'resource-one'      => RouteRegistrarControllerStubOne::class,
            'resource-two'      => RouteRegistrarControllerStubTwo::class,
            'resource-three'    => RouteRegistrarControllerStubThree::class,
        ], ['except' => ['create', 'show']]);

        $this->assertCount(15, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
        }
    }

    public function testCanRegisterResourcesWithOnlyOption()
    {
        $this->router->resources([
            'resource-one'      => RouteRegistrarControllerStubOne::class,
            'resource-two'      => RouteRegistrarControllerStubTwo::class,
            'resource-three'    => RouteRegistrarControllerStubThree::class,
        ], ['only' => ['create', 'show']]);

        $this->assertCount(6, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));
        }
    }

    public function testCanRegisterResourcesWithoutOption()
    {
        $this->router->resources([
            'resource-one'      => RouteRegistrarControllerStubOne::class,
            'resource-two'      => RouteRegistrarControllerStubTwo::class,
            'resource-three'    => RouteRegistrarControllerStubThree::class,
        ]);

        $this->assertCount(21, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));
        }
    }

    public function testCanAccessRegisteredResourceRoutesAsRouteCollection()
    {
        $resource = $this->router->middleware('resource-middleware')
                     ->resource('users', RouteRegistrarControllerStub::class)
                     ->register();

        $this->assertCount(7, $resource->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.create'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.edit'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanLimitMethodsOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->only('index', 'show', 'destroy');

        $this->assertCount(3, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanExcludeMethodsOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->except(['index', 'create', 'store', 'show', 'edit']);

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanExcludeMethodsOnRegisteredApiResource()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class)
                     ->except(['index', 'show', 'store']);

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanRegisterApiResourcesWithExceptOption()
    {
        $this->router->apiResources([
            'resource-one'      => RouteRegistrarControllerStubOne::class,
            'resource-two'      => RouteRegistrarControllerStubTwo::class,
            'resource-three'    => RouteRegistrarControllerStubThree::class,
        ], ['except' => ['create', 'show']]);

        $this->assertCount(12, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
        }
    }

    public function testCanRegisterApiResourcesWithOnlyOption()
    {
        $this->router->apiResources([
            'resource-one'      => RouteRegistrarControllerStubOne::class,
            'resource-two'      => RouteRegistrarControllerStubTwo::class,
            'resource-three'    => RouteRegistrarControllerStubThree::class,
        ], ['only' => ['index', 'show']]);

        $this->assertCount(6, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
        }
    }

    public function testCanRegisterApiResourcesWithoutOption()
    {
        $this->router->apiResources([
            'resource-one'      => RouteRegistrarControllerStubOne::class,
            'resource-two'      => RouteRegistrarControllerStubTwo::class,
            'resource-three'    => RouteRegistrarControllerStubThree::class,
        ]);

        $this->assertCount(15, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
        }
    }

    public function testUserCanRegisterApiResource()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class);

        $this->assertCount(5, $this->router->getRoutes());

        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.create'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.edit'));
    }

    public function testUserCanRegisterApiResourceWithExceptOption()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class, [
            'except' => ['destroy'],
        ]);

        $this->assertCount(4, $this->router->getRoutes());

        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.create'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.edit'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testUserCanRegisterApiResourceWithOnlyOption()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class, [
            'only' => ['index', 'show'],
        ]);

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.show'));
    }

    public function testCanNameRoutesOnRegisteredResource()
    {
        $this->router->resource('comments', RouteRegistrarControllerStub::class)
                     ->only('create', 'store')->names('reply');

        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->only('create', 'store')->names([
                         'create' => 'user.build',
                         'store' => 'user.save',
                     ]);

        $this->router->resource('posts', RouteRegistrarControllerStub::class)
                    ->only('create', 'destroy')
                    ->name('create', 'posts.make')
                    ->name('destroy', 'posts.remove');

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('reply.create'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('reply.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.build'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.save'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('posts.make'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('posts.remove'));
    }

    public function testCanOverrideParametersOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->parameters(['users' => 'admin_user']);

        $this->router->resource('posts', RouteRegistrarControllerStub::class)
                     ->parameter('posts', 'topic');

        $this->assertStringContainsString('admin_user', $this->router->getRoutes()->getByName('users.show')->uri);
        $this->assertStringContainsString('topic', $this->router->getRoutes()->getByName('posts.show')->uri);
    }

    public function testCanSetMiddlewareOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->middleware(RouteRegistrarMiddlewareStub::class);

        $this->seeMiddleware(RouteRegistrarMiddlewareStub::class);
    }

    public function testCanSetRouteName()
    {
        $this->router->as('users.index')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals('users.index', $this->getRoute()->getName());
    }

    public function testCanSetRouteNameUsingNameAlias()
    {
        $this->router->name('users.index')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals('users.index', $this->getRoute()->getName());
    }

    /**
     * Get the last route registered with the router.
     *
     * @return \Illuminate\Routing\Route
     */
    protected function getRoute()
    {
        return last($this->router->getRoutes()->get());
    }

    /**
     * Assert that the last route has the given middleware.
     *
     * @param  string  $middleware
     * @return void
     */
    protected function seeMiddleware($middleware)
    {
        $this->assertEquals($middleware, $this->getRoute()->middleware()[0]);
    }

    /**
     * Assert that the last route has the given content.
     *
     * @param  string  $content
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function seeResponse($content, Request $request)
    {
        $route = $this->getRoute();

        $this->assertTrue($route->matches($request));

        $this->assertEquals($content, $route->bind($request)->run());
    }
}

class RouteRegistrarControllerStub
{
    public function index()
    {
        return 'controller';
    }

    public function destroy()
    {
        return 'deleted';
    }
}

class RouteRegistrarMiddlewareStub
{
    //
}
