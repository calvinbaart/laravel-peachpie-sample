<?php

namespace Illuminate\Tests\Auth;

use stdClass;
use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Container\Container;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Config\Repository as Config;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;

class AuthenticateMiddlewareTest extends TestCase
{
    protected $auth;

    protected function setUp(): void
    {
        $container = Container::setInstance(new Container);

        $this->auth = new AuthManager($container);

        $container->singleton('config', function () {
            return $this->createConfig();
        });
    }

    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    public function testDefaultUnauthenticatedThrows()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $this->registerAuthDriver('default', false);

        $this->authenticate();
    }

    public function testDefaultUnauthenticatedThrowsWithGuards()
    {
        try {
            $this->registerAuthDriver('default', false);

            $this->authenticate('default');
        } catch (AuthenticationException $e) {
            $this->assertContains('default', $e->guards());

            return;
        }

        return $this->fail();
    }

    public function testDefaultAuthenticatedKeepsDefaultDriver()
    {
        $driver = $this->registerAuthDriver('default', true);

        $this->authenticate();

        $this->assertSame($driver, $this->auth->guard());
    }

    public function testSecondaryAuthenticatedUpdatesDefaultDriver()
    {
        $this->registerAuthDriver('default', false);

        $secondary = $this->registerAuthDriver('secondary', true);

        $this->authenticate('secondary');

        $this->assertSame($secondary, $this->auth->guard());
    }

    public function testMultipleDriversUnauthenticatedThrows()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $this->registerAuthDriver('default', false);

        $this->registerAuthDriver('secondary', false);

        $this->authenticate('default', 'secondary');
    }

    public function testMultipleDriversUnauthenticatedThrowsWithGuards()
    {
        $expectedGuards = ['default', 'secondary'];

        try {
            $this->registerAuthDriver('default', false);

            $this->registerAuthDriver('secondary', false);

            $this->authenticate(...$expectedGuards);
        } catch (AuthenticationException $e) {
            $this->assertEquals($expectedGuards, $e->guards());

            return;
        }

        return $this->fail();
    }

    public function testMultipleDriversAuthenticatedUpdatesDefault()
    {
        $this->registerAuthDriver('default', false);

        $secondary = $this->registerAuthDriver('secondary', true);

        $this->authenticate('default', 'secondary');

        $this->assertSame($secondary, $this->auth->guard());
    }

    /**
     * Create a new config repository instance.
     *
     * @return \Illuminate\Config\Repository
     */
    protected function createConfig()
    {
        return new Config([
            'auth' => [
                'defaults' => ['guard' => 'default'],
                'guards' => [
                    'default' => ['driver' => 'default'],
                    'secondary' => ['driver' => 'secondary'],
                ],
            ],
        ]);
    }

    /**
     * Create and register a new auth driver with the auth manager.
     *
     * @param  string  $name
     * @param  bool  $authenticated
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function registerAuthDriver($name, $authenticated)
    {
        $driver = $this->createAuthDriver($authenticated);

        $this->auth->extend($name, function () use ($driver) {
            return $driver;
        });

        return $driver;
    }

    /**
     * Create a new auth driver.
     *
     * @param  bool  $authenticated
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function createAuthDriver($authenticated)
    {
        return new RequestGuard(function () use ($authenticated) {
            return $authenticated ? new stdClass : null;
        }, m::mock(Request::class), m::mock(EloquentUserProvider::class));
    }

    /**
     * Call the authenticate middleware with the given guards.
     *
     * @param  string  ...$guards
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function authenticate(...$guards)
    {
        $request = m::mock(Request::class);

        $nextParam = null;

        $next = function ($param) use (&$nextParam) {
            $nextParam = $param;
        };

        (new Authenticate($this->auth))->handle($request, $next, ...$guards);

        $this->assertSame($request, $nextParam);
    }
}
