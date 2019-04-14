<?php

namespace Illuminate\Tests\Notifications;

use stdClass;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Contracts\Notifications\Dispatcher;

class NotificationRoutesNotificationsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    public function testNotificationCanBeDispatched()
    {
        $container = new Container;
        $factory = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $factory);
        $notifiable = new RoutesNotificationsTestInstance;
        $instance = new stdClass;
        $factory->shouldReceive('send')->with($notifiable, $instance);
        Container::setInstance($container);

        $notifiable->notify($instance);
    }

    public function testNotificationCanBeSentNow()
    {
        $container = new Container;
        $factory = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $factory);
        $notifiable = new RoutesNotificationsTestInstance;
        $instance = new stdClass;
        $factory->shouldReceive('sendNow')->with($notifiable, $instance, null);
        Container::setInstance($container);

        $notifiable->notifyNow($instance);
    }

    public function testNotificationOptionRouting()
    {
        $instance = new RoutesNotificationsTestInstance;
        $this->assertEquals('bar', $instance->routeNotificationFor('foo'));
        $this->assertEquals('taylor@laravel.com', $instance->routeNotificationFor('mail'));
        $this->assertEquals('5555555555', $instance->routeNotificationFor('nexmo'));
    }
}

class RoutesNotificationsTestInstance
{
    use RoutesNotifications;

    protected $email = 'taylor@laravel.com';
    protected $phone_number = '5555555555';

    public function routeNotificationForFoo()
    {
        return 'bar';
    }
}
