<?php

namespace Illuminate\Tests\Integration\Notifications;

use Mockery as m;
use Illuminate\Mail\Message;
use Illuminate\Mail\Markdown;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * @group integration
 */
class SendingMailNotificationsTest extends TestCase
{
    public $mailer;
    public $markdown;

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->mailer = m::mock(Mailer::class);
        $this->markdown = m::mock(Markdown::class);

        $app->extend(Markdown::class, function () {
            return $this->markdown;
        });

        $app->extend(Mailer::class, function () {
            return $this->mailer;
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name')->nullable();
        });
    }

    public function test_mail_is_sent()
    {
        $notification = new TestMailNotification;

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->markdown->shouldReceive('render')->once()->andReturn('htmlContent');
        $this->markdown->shouldReceive('renderText')->once()->andReturn('textContent');

        $this->mailer->shouldReceive('send')->once()->with(
            ['html' => 'htmlContent', 'text' => 'textContent'],
            array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]),
            m::on(function ($closure) {
                $message = m::mock(Message::class);

                $message->shouldReceive('to')->once()->with(['taylor@laravel.com']);

                $message->shouldReceive('cc')->once()->with('cc@deepblue.com', 'cc');

                $message->shouldReceive('bcc')->once()->with('bcc@deepblue.com', 'bcc');

                $message->shouldReceive('from')->once()->with('jack@deepblue.com', 'Jacques Mayol');

                $message->shouldReceive('replyTo')->once()->with('jack@deepblue.com', 'Jacques Mayol');

                $message->shouldReceive('subject')->once()->with('Test Mail Notification');

                $message->shouldReceive('setPriority')->once()->with(1);

                $closure($message);

                return true;
            })
        );

        $user->notify($notification);
    }

    public function test_mail_is_sent_to_named_address()
    {
        $notification = new TestMailNotification;

        $user = NotifiableUserWithNamedAddress::forceCreate([
            'email' => 'taylor@laravel.com',
            'name' => 'Taylor Otwell',
        ]);

        $this->markdown->shouldReceive('render')->once()->andReturn('htmlContent');
        $this->markdown->shouldReceive('renderText')->once()->andReturn('textContent');

        $this->mailer->shouldReceive('send')->once()->with(
            ['html' => 'htmlContent', 'text' => 'textContent'],
            array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]),
            m::on(function ($closure) {
                $message = m::mock(Message::class);

                $message->shouldReceive('to')->once()->with(['taylor@laravel.com' => 'Taylor Otwell', 'foo_taylor@laravel.com']);

                $message->shouldReceive('cc')->once()->with('cc@deepblue.com', 'cc');

                $message->shouldReceive('bcc')->once()->with('bcc@deepblue.com', 'bcc');

                $message->shouldReceive('from')->once()->with('jack@deepblue.com', 'Jacques Mayol');

                $message->shouldReceive('replyTo')->once()->with('jack@deepblue.com', 'Jacques Mayol');

                $message->shouldReceive('subject')->once()->with('Test Mail Notification');

                $message->shouldReceive('setPriority')->once()->with(1);

                $closure($message);

                return true;
            })
        );

        $user->notify($notification);
    }

    public function test_mail_is_sent_with_subject()
    {
        $notification = new TestMailNotificationWithSubject;

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->markdown->shouldReceive('render')->once()->andReturn('htmlContent');
        $this->markdown->shouldReceive('renderText')->once()->andReturn('textContent');

        $this->mailer->shouldReceive('send')->once()->with(
            ['html' => 'htmlContent', 'text' => 'textContent'],
            array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]),
            m::on(function ($closure) {
                $message = m::mock(Message::class);

                $message->shouldReceive('to')->once()->with(['taylor@laravel.com']);

                $message->shouldReceive('subject')->once()->with('mail custom subject');

                $closure($message);

                return true;
            })
        );

        $user->notify($notification);
    }

    public function test_mail_is_sent_to_multiple_adresses()
    {
        $notification = new TestMailNotificationWithSubject;

        $user = NotifiableUserWithMultipleAddreses::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $this->markdown->shouldReceive('render')->once()->andReturn('htmlContent');
        $this->markdown->shouldReceive('renderText')->once()->andReturn('textContent');

        $this->mailer->shouldReceive('send')->once()->with(
            ['html' => 'htmlContent', 'text' => 'textContent'],
            array_merge($notification->toMail($user)->toArray(), [
                '__laravel_notification' => get_class($notification),
                '__laravel_notification_queued' => false,
            ]),
            m::on(function ($closure) {
                $message = m::mock(Message::class);

                $message->shouldReceive('to')->once()->with(['foo_taylor@laravel.com', 'bar_taylor@laravel.com']);

                $message->shouldReceive('subject')->once()->with('mail custom subject');

                $closure($message);

                return true;
            })
        );

        $user->notify($notification);
    }

    public function test_mail_is_sent_using_mailable()
    {
        $notification = new TestMailNotificationWithMailable;

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify($notification);
    }
}

class NotifiableUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}

class NotifiableUserWithNamedAddress extends NotifiableUser
{
    public function routeNotificationForMail($notification)
    {
        return [
            $this->email => $this->name,
            'foo_'.$this->email,
        ];
    }
}

class NotifiableUserWithMultipleAddreses extends NotifiableUser
{
    public function routeNotificationForMail($notification)
    {
        return [
            'foo_'.$this->email,
            'bar_'.$this->email,
        ];
    }
}

class TestMailNotification extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->priority(1)
            ->cc('cc@deepblue.com', 'cc')
            ->bcc('bcc@deepblue.com', 'bcc')
            ->from('jack@deepblue.com', 'Jacques Mayol')
            ->replyTo('jack@deepblue.com', 'Jacques Mayol')
            ->line('The introduction to the notification.');
    }
}

class TestMailNotificationWithSubject extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('mail custom subject')
            ->line('The introduction to the notification.');
    }
}

class TestMailNotificationWithMailable extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        $mailable = m::mock(Mailable::class);

        $mailable->shouldReceive('send')->once();

        return $mailable;
    }
}
