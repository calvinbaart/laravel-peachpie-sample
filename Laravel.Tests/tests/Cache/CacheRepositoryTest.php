<?php

namespace Illuminate\Tests\Cache;

use DateTime;
use DateInterval;
use Mockery as m;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Store;

class CacheRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Carbon::setTestNow();
    }

    public function testGetReturnsValueFromCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArray()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => 'bar', 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $repo->get(['foo', 'bar']));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArrayWithDefaultValues()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => null, 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'default', 'bar' => 'baz'], $repo->get(['foo' => 'default', 'bar']));
    }

    public function testDefaultValueIsReturned()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->times(2)->andReturn(null);
        $this->assertEquals('bar', $repo->get('foo', 'bar'));
        $this->assertEquals('baz', $repo->get('boom', function () {
            return 'baz';
        }));
    }

    public function testSettingDefaultCacheTime()
    {
        $repo = $this->getRepository();
        $repo->setDefaultCacheTime(10);
        $this->assertEquals(10, $repo->getDefaultCacheTime());
    }

    public function testHasMethod()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->once()->with('bar')->andReturn('bar');
        $repo->getStore()->shouldReceive('get')->once()->with('baz')->andReturn(false);

        $this->assertTrue($repo->has('bar'));
        $this->assertFalse($repo->has('foo'));
        $this->assertTrue($repo->has('baz'));
    }

    public function testMissingMethod()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->once()->with('bar')->andReturn('bar');

        $this->assertTrue($repo->missing('foo'));
        $this->assertFalse($repo->missing('bar'));
    }

    public function testRememberMethodCallsPutAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 10);
        $result = $repo->remember('foo', 10, function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);

        /*
         * Use Carbon object...
         */
        Carbon::setTestNow(Carbon::now());

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->times(2)->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 602);
        $repo->getStore()->shouldReceive('put')->once()->with('baz', 'qux', 598);
        $result = $repo->remember('foo', Carbon::now()->addMinutes(10)->addSeconds(2), function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);
        $result = $repo->remember('baz', Carbon::now()->addMinutes(10)->subSeconds(2), function () {
            return 'qux';
        });
        $this->assertEquals('qux', $result);
    }

    public function testRememberForeverMethodCallsForeverAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar');
        $result = $repo->rememberForever('foo', function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);
    }

    public function testPuttingMultipleItemsInCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1);
        $repo->put(['foo' => 'bar', 'bar' => 'baz'], 1);
    }

    public function testSettingMultipleItemsInCache()
    {
        // Alias of PuttingMultiple
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1)->andReturn(true);
        $result = $repo->setMultiple(['foo' => 'bar', 'bar' => 'baz'], 1);
        $this->assertTrue($result);
    }

    public function testPutWithNullTTLRemembersItemForever()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar')->andReturn(true);
        $this->assertTrue($repo->put('foo', 'bar'));
    }

    public function testPutWithDatetimeInPastOrZeroSecondsRemovesOldItem()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->never();
        $repo->getStore()->shouldReceive('forget')->twice()->andReturn(true);
        $result = $repo->put('foo', 'bar', Carbon::now()->subMinutes(10));
        $this->assertTrue($result);
        $result = $repo->put('foo', 'bar', Carbon::now());
        $this->assertTrue($result);
    }

    public function testPutManyWithNullTTLRemembersItemsForever()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forever')->with('foo', 'bar')->andReturn(true);
        $repo->getStore()->shouldReceive('forever')->with('bar', 'baz')->andReturn(true);
        $this->assertTrue($repo->putMany(['foo' => 'bar', 'bar' => 'baz']));
    }

    public function testAddWithStoreFailureReturnsFalse()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('add')->never();
        $repo->getStore()->shouldReceive('get')->andReturn(null);
        $repo->getStore()->shouldReceive('put')->andReturn(false);
        $this->assertFalse($repo->add('foo', 'bar', 60));
    }

    public function testCacheAddCallsRedisStoreAdd()
    {
        $store = m::mock(RedisStore::class);
        $store->shouldReceive('add')->once()->with('k', 'v', 60)->andReturn(true);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 60));
    }

    public function testAddWithNullTTLRemembersItemForever()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('forever')->once()->with('foo', 'bar')->andReturn(true);
        $this->assertTrue($repo->add('foo', 'bar'));
    }

    public function testAddWithDatetimeInPastOrZeroSecondsReturnsImmediately()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('add', 'get', 'put')->never();
        $result = $repo->add('foo', 'bar', Carbon::now()->subMinutes(10));
        $this->assertFalse($result);
        $result = $repo->add('foo', 'bar', Carbon::now());
        $this->assertFalse($result);
    }

    public function dataProviderTestGetSeconds()
    {
        Carbon::setTestNow(Carbon::parse($this->getTestDate()));

        return [
            [Carbon::now()->addMinutes(5)],
            [(new DateTime($this->getTestDate()))->modify('+5 minutes')],
            [(new DateTimeImmutable($this->getTestDate()))->modify('+5 minutes')],
            [new DateInterval('PT5M')],
            [300],
        ];
    }

    /**
     * @dataProvider dataProviderTestGetSeconds
     * @param mixed $duration
     */
    public function testGetSeconds($duration)
    {
        Carbon::setTestNow(Carbon::parse($this->getTestDate()));

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->once()->with($key = 'foo', $value = 'bar', 300);
        $repo->put($key, $value, $duration);
    }

    public function testRegisterMacroWithNonStaticCall()
    {
        $repo = $this->getRepository();
        $repo::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertEquals($repo->{__CLASS__}(), 'Taylor');
    }

    public function testForgettingCacheKey()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->forget('a-key');
    }

    public function testRemovingCacheKey()
    {
        // Alias of Forget
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->delete('a-key');
    }

    public function testSettingCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->with($key = 'foo', $value = 'bar', 1)->andReturn(true);
        $result = $repo->set($key, $value, 1);
        $this->assertTrue($result);
    }

    public function testClearingWholeCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('flush')->andReturn(true);
        $repo->clear();
    }

    public function testGettingMultipleValuesFromCache()
    {
        $keys = ['key1', 'key2', 'key3'];
        $default = ['key2' => 5];

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['key2', 'key1', 'key3'])->andReturn(['key1' => 1, 'key2' => null, 'key3' => null]);
        $this->assertEquals(['key1' => 1, 'key2' => 5, 'key3' => null], $repo->getMultiple($keys, $default));
    }

    public function testRemovingMultipleKeys()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->getStore()->shouldReceive('forget')->once()->with('a-second-key')->andReturn(true);

        $this->assertTrue($repo->deleteMultiple(['a-key', 'a-second-key']));
    }

    public function testRemovingMultipleKeysFailsIfOneFails()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->getStore()->shouldReceive('forget')->once()->with('a-second-key')->andReturn(false);

        $this->assertFalse($repo->deleteMultiple(['a-key', 'a-second-key']));
    }

    public function testAllTagsArePassedToTaggableStore()
    {
        $store = m::mock(ArrayStore::class);
        $repo = new Repository($store);

        $taggedCache = m::mock();
        $taggedCache->shouldReceive('setDefaultCacheTime');
        $store->shouldReceive('tags')->once()->with(['foo', 'bar', 'baz'])->andReturn($taggedCache);
        $repo->tags('foo', 'bar', 'baz');
    }

    protected function getRepository()
    {
        $dispatcher = new Dispatcher(m::mock(Container::class));
        $repository = new Repository(m::mock(Store::class));

        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }

    protected function getTestDate()
    {
        return '2030-07-25 12:13:14 UTC';
    }
}
