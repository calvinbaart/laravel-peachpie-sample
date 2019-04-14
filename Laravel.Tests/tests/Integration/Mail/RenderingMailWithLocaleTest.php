<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Mailable;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\View;

/**
 * @group integration
 */
class RenderingMailWithLocaleTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.locale', 'en');

        View::addLocation(__DIR__.'/Fixtures');

        app('translator')->setLoaded([
            '*' => [
                '*' => [
                    'en' => ['nom' => 'name'],
                    'es' => ['nom' => 'nombre'],
                ],
            ],
        ]);
    }

    public function testMailableRendersInDefaultLocale()
    {
        $mail = new RenderedTestMail;

        $this->assertStringContainsString('name'.PHP_EOL, $mail->render());
    }

    public function testMailableRendersInSelectedLocale()
    {
        $mail = (new RenderedTestMail)->locale('es');

        $this->assertStringContainsString('nombre'.PHP_EOL, $mail->render());
    }

    public function testMailableRendersInAppSelectedLocale()
    {
        $this->app->setLocale('es');

        $mail = new RenderedTestMail;

        $this->assertStringContainsString('nombre'.PHP_EOL, $mail->render());
    }
}

class RenderedTestMail extends Mailable
{
    public function build()
    {
        return $this->view('view');
    }
}
