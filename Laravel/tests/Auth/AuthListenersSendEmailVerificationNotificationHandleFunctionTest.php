<?php

namespace Illuminate\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

class AuthListenersSendEmailVerificationNotificationHandleFunctionTest extends TestCase
{
    /**
     * @return void
     */
    public function testWillExecuted()
    {
        $user = $this->getMockBuilder(MustVerifyEmail::class)->getMock();
        $user->method('hasVerifiedEmail')->willReturn(false);
        $user->expects($this->once())->method('sendEmailVerificationNotification');

        $listener = new SendEmailVerificationNotification;

        $listener->handle(new Registered($user));
    }

    /**
     * @return void
     */
    public function testUserIsNotInstanceOfMustVerifyEmail()
    {
        $user = $this->getMockBuilder(User::class)->getMock();
        $user->expects($this->never())->method('sendEmailVerificationNotification');

        $listener = new SendEmailVerificationNotification;

        $listener->handle(new Registered($user));
    }

    /**
     * @return void
     */
    public function testHasVerifiedEmailAsTrue()
    {
        $user = $this->getMockBuilder(MustVerifyEmail::class)->getMock();
        $user->method('hasVerifiedEmail')->willReturn(true);
        $user->expects($this->never())->method('sendEmailVerificationNotification');

        $listener = new SendEmailVerificationNotification;

        $listener->handle(new Registered($user));
    }
}
