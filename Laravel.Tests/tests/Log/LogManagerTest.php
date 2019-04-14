<?php

namespace Illuminate\Tests\Log;

use ReflectionProperty;
use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Monolog\Logger as Monolog;
use Orchestra\Testbench\TestCase;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NewRelicHandler;
use Monolog\Handler\LogEntriesHandler;
use Monolog\Formatter\NormalizerFormatter;

class LogManagerTest extends TestCase
{
    public function testLogManagerCachesLoggerInstances()
    {
        $manager = new LogManager($this->app);

        $logger1 = $manager->channel('single')->getLogger();
        $logger2 = $manager->channel('single')->getLogger();

        $this->assertSame($logger1, $logger2);
    }

    public function testStackChannel()
    {
        $config = $this->app['config'];

        $config->set('logging.channels.stack', [
            'driver' => 'stack',
            'channels' => ['stderr', 'stdout'],
        ]);

        $config->set('logging.channels.stderr', [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' => 'notice',
            'with' => [
                'stream' => 'php://stderr',
                'bubble' => false,
            ],
        ]);

        $config->set('logging.channels.stdout', [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' => 'info',
            'with' => [
                'stream' => 'php://stdout',
                'bubble' => true,
            ],
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('stack');
        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(2, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertInstanceOf(StreamHandler::class, $handlers[1]);
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
        $this->assertEquals(Monolog::INFO, $handlers[1]->getLevel());
        $this->assertFalse($handlers[0]->getBubble());
        $this->assertTrue($handlers[1]->getBubble());
    }

    public function testLogManagerCreatesConfiguredMonologHandler()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.nonbubblingstream', [
            'driver' => 'monolog',
            'name' => 'foobar',
            'handler' => StreamHandler::class,
            'level' => 'notice',
            'with' => [
                'stream' => 'php://stderr',
                'bubble' => false,
            ],
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('nonbubblingstream');
        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals('foobar', $logger->getName());
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
        $this->assertFalse($handlers[0]->getBubble());

        $url = new ReflectionProperty(get_class($handlers[0]), 'url');
        $url->setAccessible(true);
        $this->assertEquals('php://stderr', $url->getValue($handlers[0]));

        $config->set('logging.channels.logentries', [
            'driver' => 'monolog',
            'name' => 'le',
            'handler' => LogEntriesHandler::class,
            'with' => [
                'token' => '123456789',
            ],
        ]);

        $logger = $manager->channel('logentries');
        $handlers = $logger->getLogger()->getHandlers();

        $logToken = new ReflectionProperty(get_class($handlers[0]), 'logToken');
        $logToken->setAccessible(true);

        $this->assertInstanceOf(LogEntriesHandler::class, $handlers[0]);
        $this->assertEquals('123456789', $logToken->getValue($handlers[0]));
    }

    public function testLogManagerCreatesMonologHandlerWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.newrelic', [
            'driver' => 'monolog',
            'name' => 'nr',
            'handler' => NewRelicHandler::class,
            'formatter' => 'default',
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('newrelic');
        $handler = $logger->getLogger()->getHandlers()[0];

        $this->assertInstanceOf(NewRelicHandler::class, $handler);
        $this->assertInstanceOf(NormalizerFormatter::class, $handler->getFormatter());

        $config->set('logging.channels.newrelic2', [
            'driver' => 'monolog',
            'name' => 'nr',
            'handler' => NewRelicHandler::class,
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('newrelic2');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(NewRelicHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testLogManagerCreateSingleDriverWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.defaultsingle', [
            'driver' => 'single',
            'name' => 'ds',
            'path' => storage_path('logs/laravel.log'),
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultsingle');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $config->set('logging.channels.formattedsingle', [
            'driver' => 'single',
            'name' => 'fs',
            'path' => storage_path('logs/laravel.log'),
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('formattedsingle');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testLogManagerCreateDailyDriverWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.defaultdaily', [
            'driver' => 'daily',
            'name' => 'dd',
            'path' => storage_path('logs/laravel.log'),
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultdaily');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $config->set('logging.channels.formatteddaily', [
            'driver' => 'daily',
            'name' => 'fd',
            'path' => storage_path('logs/laravel.log'),
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('formatteddaily');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testLogManagerCreateSyslogDriverWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.defaultsyslog', [
            'driver' => 'syslog',
            'name' => 'ds',
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultsyslog');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(SyslogHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $config->set('logging.channels.formattedsyslog', [
            'driver' => 'syslog',
            'name' => 'fs',
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('formattedsyslog');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(SyslogHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertEquals('Y/m/d--test', $dateFormat->getValue($formatter));
    }
}
