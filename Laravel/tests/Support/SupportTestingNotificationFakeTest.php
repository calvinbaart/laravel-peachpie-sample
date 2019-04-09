<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notification;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Illuminate\Contracts\Translation\HasLocalePreference;

class SupportTestingNotificationFakeTest extends TestCase
{
    /**
     * @var NotificationFake
     */
    private $fake;

    /**
     * @var NotificationStub
     */
    private $notification;

    /**
     * @var UserStub
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new NotificationFake;
        $this->notification = new NotificationStub;
        $this->user = new UserStub;
    }

    public function testAssertSentTo()
    {
        try {
            $this->fake->assertSentTo($this->user, NotificationStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\NotificationStub] notification was not sent.'));
        }

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->assertSentTo($this->user, NotificationStub::class);
    }

    public function testAssertNotSentTo()
    {
        $this->fake->assertNotSentTo($this->user, NotificationStub::class);

        $this->fake->send($this->user, new NotificationStub);

        try {
            $this->fake->assertNotSentTo($this->user, NotificationStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\NotificationStub] notification was sent.'));
        }
    }

    public function testResettingNotificationId()
    {
        $notification = new NotificationStub;

        $this->fake->send($this->user, $notification);

        $id = $notification->id;

        $this->fake->send($this->user, $notification);

        $this->assertSame($id, $notification->id);

        $notification->id = null;

        $this->fake->send($this->user, $notification);

        $this->assertNotNull($notification->id);
        $this->assertNotSame($id, $notification->id);
    }

    public function testAssertTimesSent()
    {
        $this->fake->assertTimesSent(0, NotificationStub::class);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->send(new UserStub, new NotificationStub);

        $this->fake->assertTimesSent(3, NotificationStub::class);
    }

    public function testAssertSentToWhenNotifiableHasPreferredLocale()
    {
        $user = new LocalizedUserStub;

        $this->fake->send($user, new NotificationStub);

        $this->fake->assertSentTo($user, NotificationStub::class, function ($notification, $channels, $notifiable, $locale) use ($user) {
            return $notifiable === $user && $locale === 'au';
        });
    }
}

class NotificationStub extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }
}

class UserStub extends User
{
    //
}

class LocalizedUserStub extends User implements HasLocalePreference
{
    public function preferredLocale()
    {
        return 'au';
    }
}
