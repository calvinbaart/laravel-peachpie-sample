<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;
use PHPUnit\Framework\TestCase;

class SupportStrTest extends TestCase
{
    public function testStringCanBeLimitedByWords()
    {
        $this->assertEquals('Taylor...', Str::words('Taylor Otwell', 1));
        $this->assertEquals('Taylor___', Str::words('Taylor Otwell', 1, '___'));
        $this->assertEquals('Taylor Otwell', Str::words('Taylor Otwell', 3));
    }

    public function testStringTrimmedOnlyWhereNecessary()
    {
        $this->assertEquals(' Taylor Otwell ', Str::words(' Taylor Otwell ', 3));
        $this->assertEquals(' Taylor...', Str::words(' Taylor Otwell ', 1));
    }

    public function testStringTitle()
    {
        $this->assertEquals('Jefferson Costella', Str::title('jefferson costella'));
        $this->assertEquals('Jefferson Costella', Str::title('jefFErson coSTella'));
    }

    public function testStringWithoutWordsDoesntProduceError()
    {
        $nbsp = chr(0xC2).chr(0xA0);
        $this->assertEquals(' ', Str::words(' '));
        $this->assertEquals($nbsp, Str::words($nbsp));
    }

    public function testStringAscii()
    {
        $this->assertEquals('@', Str::ascii('@'));
        $this->assertEquals('u', Str::ascii('ü'));
    }

    public function testStringAsciiWithSpecificLocale()
    {
        $this->assertSame('h H sht SHT a A y Y', Str::ascii('х Х щ Щ ъ Ъ ь Ь', 'bg'));
        $this->assertSame('ae oe ue AE OE UE', Str::ascii('ä ö ü Ä Ö Ü', 'de'));
    }

    public function testStartsWith()
    {
        $this->assertTrue(Str::startsWith('jason', 'jas'));
        $this->assertTrue(Str::startsWith('jason', 'jason'));
        $this->assertTrue(Str::startsWith('jason', ['jas']));
        $this->assertTrue(Str::startsWith('jason', ['day', 'jas']));
        $this->assertFalse(Str::startsWith('jason', 'day'));
        $this->assertFalse(Str::startsWith('jason', ['day']));
        $this->assertFalse(Str::startsWith('jason', ''));
        $this->assertFalse(Str::startsWith('7', ' 7'));
        $this->assertTrue(Str::startsWith('7a', '7'));
        $this->assertTrue(Str::startsWith('7a', 7));
        $this->assertTrue(Str::startsWith('7.12a', 7.12));
        $this->assertFalse(Str::startsWith('7.12a', 7.13));
        $this->assertTrue(Str::startsWith(7.123, '7'));
        $this->assertTrue(Str::startsWith(7.123, '7.12'));
        $this->assertFalse(Str::startsWith(7.123, '7.13'));
        // Test for multibyte string support
        $this->assertTrue(Str::startsWith('Jönköping', 'Jö'));
        $this->assertTrue(Str::startsWith('Malmö', 'Malmö'));
        $this->assertFalse(Str::startsWith('Jönköping', 'Jonko'));
        $this->assertFalse(Str::startsWith('Malmö', 'Malmo'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::endsWith('jason', 'on'));
        $this->assertTrue(Str::endsWith('jason', 'jason'));
        $this->assertTrue(Str::endsWith('jason', ['on']));
        $this->assertTrue(Str::endsWith('jason', ['no', 'on']));
        $this->assertFalse(Str::endsWith('jason', 'no'));
        $this->assertFalse(Str::endsWith('jason', ['no']));
        $this->assertFalse(Str::endsWith('jason', ''));
        $this->assertFalse(Str::endsWith('7', ' 7'));
        $this->assertTrue(Str::endsWith('a7', '7'));
        $this->assertTrue(Str::endsWith('a7', 7));
        $this->assertTrue(Str::endsWith('a7.12', 7.12));
        $this->assertFalse(Str::endsWith('a7.12', 7.13));
        $this->assertTrue(Str::endsWith(0.27, '7'));
        $this->assertTrue(Str::endsWith(0.27, '0.27'));
        $this->assertFalse(Str::endsWith(0.27, '8'));
        // Test for multibyte string support
        $this->assertTrue(Str::endsWith('Jönköping', 'öping'));
        $this->assertTrue(Str::endsWith('Malmö', 'mö'));
        $this->assertFalse(Str::endsWith('Jönköping', 'oping'));
        $this->assertFalse(Str::endsWith('Malmö', 'mo'));
    }

    public function testStrBefore()
    {
        $this->assertEquals('han', Str::before('hannah', 'nah'));
        $this->assertEquals('ha', Str::before('hannah', 'n'));
        $this->assertEquals('ééé ', Str::before('ééé hannah', 'han'));
        $this->assertEquals('hannah', Str::before('hannah', 'xxxx'));
        $this->assertEquals('hannah', Str::before('hannah', ''));
        $this->assertEquals('han', Str::before('han0nah', '0'));
        $this->assertEquals('han', Str::before('han0nah', 0));
        $this->assertEquals('han', Str::before('han2nah', 2));
    }

    public function testStrAfter()
    {
        $this->assertEquals('nah', Str::after('hannah', 'han'));
        $this->assertEquals('nah', Str::after('hannah', 'n'));
        $this->assertEquals('nah', Str::after('ééé hannah', 'han'));
        $this->assertEquals('hannah', Str::after('hannah', 'xxxx'));
        $this->assertEquals('hannah', Str::after('hannah', ''));
        $this->assertEquals('nah', Str::after('han0nah', '0'));
        $this->assertEquals('nah', Str::after('han0nah', 0));
        $this->assertEquals('nah', Str::after('han2nah', 2));
    }

    public function testStrContains()
    {
        $this->assertTrue(Str::contains('taylor', 'ylo'));
        $this->assertTrue(Str::contains('taylor', 'taylor'));
        $this->assertTrue(Str::contains('taylor', ['ylo']));
        $this->assertTrue(Str::contains('taylor', ['xxx', 'ylo']));
        $this->assertFalse(Str::contains('taylor', 'xxx'));
        $this->assertFalse(Str::contains('taylor', ['xxx']));
        $this->assertFalse(Str::contains('taylor', ''));
    }

    public function testParseCallback()
    {
        $this->assertEquals(['Class', 'method'], Str::parseCallback('Class@method', 'foo'));
        $this->assertEquals(['Class', 'foo'], Str::parseCallback('Class', 'foo'));
    }

    public function testSlug()
    {
        $this->assertEquals('hello-world', Str::slug('hello world'));
        $this->assertEquals('hello-world', Str::slug('hello-world'));
        $this->assertEquals('hello-world', Str::slug('hello_world'));
        $this->assertEquals('hello_world', Str::slug('hello_world', '_'));
        $this->assertEquals('user-at-host', Str::slug('user@host'));
        $this->assertEquals('سلام-دنیا', Str::slug('سلام دنیا', '-', null));
    }

    public function testStrStart()
    {
        $this->assertEquals('/test/string', Str::start('test/string', '/'));
        $this->assertEquals('/test/string', Str::start('/test/string', '/'));
        $this->assertEquals('/test/string', Str::start('//test/string', '/'));
    }

    public function testFinish()
    {
        $this->assertEquals('abbc', Str::finish('ab', 'bc'));
        $this->assertEquals('abbc', Str::finish('abbcbc', 'bc'));
        $this->assertEquals('abcbbc', Str::finish('abcbbcbc', 'bc'));
    }

    public function testIs()
    {
        $this->assertTrue(Str::is('/', '/'));
        $this->assertFalse(Str::is('/', ' /'));
        $this->assertFalse(Str::is('/', '/a'));
        $this->assertTrue(Str::is('foo/*', 'foo/bar/baz'));

        $this->assertTrue(Str::is('*@*', 'App\Class@method'));
        $this->assertTrue(Str::is('*@*', 'app\Class@'));
        $this->assertTrue(Str::is('*@*', '@method'));

        // is case sensitive
        $this->assertFalse(Str::is('*BAZ*', 'foo/bar/baz'));
        $this->assertFalse(Str::is('*FOO*', 'foo/bar/baz'));
        $this->assertFalse(Str::is('A', 'a'));

        // Accepts array of patterns
        $this->assertTrue(Str::is(['a*', 'b*'], 'a/'));
        $this->assertTrue(Str::is(['a*', 'b*'], 'b/'));
        $this->assertFalse(Str::is(['a*', 'b*'], 'f/'));

        // numeric values and patterns
        $this->assertFalse(Str::is(['a*', 'b*'], 123));
        $this->assertTrue(Str::is(['*2*', 'b*'], 11211));

        $this->assertTrue(Str::is('*/foo', 'blah/baz/foo'));

        $valueObject = new StringableObjectStub('foo/bar/baz');
        $patternObject = new StringableObjectStub('foo/*');

        $this->assertTrue(Str::is('foo/bar/baz', $valueObject));
        $this->assertTrue(Str::is($patternObject, $valueObject));

        //empty patterns
        $this->assertFalse(Str::is([], 'test'));
    }

    public function testKebab()
    {
        $this->assertEquals('laravel-php-framework', Str::kebab('LaravelPhpFramework'));
    }

    public function testLower()
    {
        $this->assertEquals('foo bar baz', Str::lower('FOO BAR BAZ'));
        $this->assertEquals('foo bar baz', Str::lower('fOo Bar bAz'));
    }

    public function testUpper()
    {
        $this->assertEquals('FOO BAR BAZ', Str::upper('foo bar baz'));
        $this->assertEquals('FOO BAR BAZ', Str::upper('foO bAr BaZ'));
    }

    public function testLimit()
    {
        $this->assertEquals('Laravel is...', Str::limit('Laravel is a free, open source PHP web application framework.', 10));
        $this->assertEquals('这是一...', Str::limit('这是一段中文', 6));

        $string = 'The PHP framework for web artisans.';
        $this->assertEquals('The PHP...', Str::limit($string, 7));
        $this->assertEquals('The PHP', Str::limit($string, 7, ''));
        $this->assertEquals('The PHP framework for web artisans.', Str::limit($string, 100));

        $nonAsciiString = '这是一段中文';
        $this->assertEquals('这是一...', Str::limit($nonAsciiString, 6));
        $this->assertEquals('这是一', Str::limit($nonAsciiString, 6, ''));
    }

    public function testLength()
    {
        $this->assertEquals(11, Str::length('foo bar baz'));
        $this->assertEquals(11, Str::length('foo bar baz', 'UTF-8'));
    }

    public function testRandom()
    {
        $this->assertEquals(16, strlen(Str::random()));
        $randomInteger = random_int(1, 100);
        $this->assertEquals($randomInteger, strlen(Str::random($randomInteger)));
        $this->assertIsString(Str::random());
    }

    public function testReplaceArray()
    {
        $this->assertEquals('foo/bar/baz', Str::replaceArray('?', ['foo', 'bar', 'baz'], '?/?/?'));
        $this->assertEquals('foo/bar/baz/?', Str::replaceArray('?', ['foo', 'bar', 'baz'], '?/?/?/?'));
        $this->assertEquals('foo/bar', Str::replaceArray('?', ['foo', 'bar', 'baz'], '?/?'));
        $this->assertEquals('?/?/?', Str::replaceArray('x', ['foo', 'bar', 'baz'], '?/?/?'));
    }

    public function testReplaceFirst()
    {
        $this->assertEquals('fooqux foobar', Str::replaceFirst('bar', 'qux', 'foobar foobar'));
        $this->assertEquals('foo/qux? foo/bar?', Str::replaceFirst('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertEquals('foo foobar', Str::replaceFirst('bar', '', 'foobar foobar'));
        $this->assertEquals('foobar foobar', Str::replaceFirst('xxx', 'yyy', 'foobar foobar'));
        $this->assertEquals('foobar foobar', Str::replaceFirst('', 'yyy', 'foobar foobar'));
        // Test for multibyte string support
        $this->assertEquals('Jxxxnköping Malmö', Str::replaceFirst('ö', 'xxx', 'Jönköping Malmö'));
        $this->assertEquals('Jönköping Malmö', Str::replaceFirst('', 'yyy', 'Jönköping Malmö'));
    }

    public function testReplaceLast()
    {
        $this->assertEquals('foobar fooqux', Str::replaceLast('bar', 'qux', 'foobar foobar'));
        $this->assertEquals('foo/bar? foo/qux?', Str::replaceLast('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertEquals('foobar foo', Str::replaceLast('bar', '', 'foobar foobar'));
        $this->assertEquals('foobar foobar', Str::replaceLast('xxx', 'yyy', 'foobar foobar'));
        $this->assertEquals('foobar foobar', Str::replaceLast('', 'yyy', 'foobar foobar'));
        // Test for multibyte string support
        $this->assertEquals('Malmö Jönkxxxping', Str::replaceLast('ö', 'xxx', 'Malmö Jönköping'));
        $this->assertEquals('Malmö Jönköping', Str::replaceLast('', 'yyy', 'Malmö Jönköping'));
    }

    public function testSnake()
    {
        $this->assertEquals('laravel_p_h_p_framework', Str::snake('LaravelPHPFramework'));
        $this->assertEquals('laravel_php_framework', Str::snake('LaravelPhpFramework'));
        $this->assertEquals('laravel php framework', Str::snake('LaravelPhpFramework', ' '));
        $this->assertEquals('laravel_php_framework', Str::snake('Laravel Php Framework'));
        $this->assertEquals('laravel_php_framework', Str::snake('Laravel    Php      Framework   '));
        // ensure cache keys don't overlap
        $this->assertEquals('laravel__php__framework', Str::snake('LaravelPhpFramework', '__'));
        $this->assertEquals('laravel_php_framework_', Str::snake('LaravelPhpFramework_', '_'));
        $this->assertEquals('laravel_php_framework', Str::snake('laravel php Framework'));
        $this->assertEquals('laravel_php_frame_work', Str::snake('laravel php FrameWork'));
    }

    public function testStudly()
    {
        $this->assertEquals('LaravelPHPFramework', Str::studly('laravel_p_h_p_framework'));
        $this->assertEquals('LaravelPhpFramework', Str::studly('laravel_php_framework'));
        $this->assertEquals('LaravelPhPFramework', Str::studly('laravel-phP-framework'));
        $this->assertEquals('LaravelPhpFramework', Str::studly('laravel  -_-  php   -_-   framework   '));

        $this->assertEquals('FooBar', Str::studly('fooBar'));
        $this->assertEquals('FooBar', Str::studly('foo_bar'));
        $this->assertEquals('FooBar', Str::studly('foo_bar')); // test cache
        $this->assertEquals('FooBarBaz', Str::studly('foo-barBaz'));
        $this->assertEquals('FooBarBaz', Str::studly('foo-bar_baz'));
    }

    public function testCamel()
    {
        $this->assertEquals('laravelPHPFramework', Str::camel('Laravel_p_h_p_framework'));
        $this->assertEquals('laravelPhpFramework', Str::camel('Laravel_php_framework'));
        $this->assertEquals('laravelPhPFramework', Str::camel('Laravel-phP-framework'));
        $this->assertEquals('laravelPhpFramework', Str::camel('Laravel  -_-  php   -_-   framework   '));

        $this->assertEquals('fooBar', Str::camel('FooBar'));
        $this->assertEquals('fooBar', Str::camel('foo_bar'));
        $this->assertEquals('fooBar', Str::camel('foo_bar')); // test cache
        $this->assertEquals('fooBarBaz', Str::camel('Foo-barBaz'));
        $this->assertEquals('fooBarBaz', Str::camel('foo-bar_baz'));
    }

    public function testSubstr()
    {
        $this->assertEquals('Ё', Str::substr('БГДЖИЛЁ', -1));
        $this->assertEquals('ЛЁ', Str::substr('БГДЖИЛЁ', -2));
        $this->assertEquals('И', Str::substr('БГДЖИЛЁ', -3, 1));
        $this->assertEquals('ДЖИЛ', Str::substr('БГДЖИЛЁ', 2, -1));
        $this->assertEmpty(Str::substr('БГДЖИЛЁ', 4, -4));
        $this->assertEquals('ИЛ', Str::substr('БГДЖИЛЁ', -3, -1));
        $this->assertEquals('ГДЖИЛЁ', Str::substr('БГДЖИЛЁ', 1));
        $this->assertEquals('ГДЖ', Str::substr('БГДЖИЛЁ', 1, 3));
        $this->assertEquals('БГДЖ', Str::substr('БГДЖИЛЁ', 0, 4));
        $this->assertEquals('Ё', Str::substr('БГДЖИЛЁ', -1, 1));
        $this->assertEmpty(Str::substr('Б', 2));
    }

    public function testUcfirst()
    {
        $this->assertEquals('Laravel', Str::ucfirst('laravel'));
        $this->assertEquals('Laravel framework', Str::ucfirst('laravel framework'));
        $this->assertEquals('Мама', Str::ucfirst('мама'));
        $this->assertEquals('Мама мыла раму', Str::ucfirst('мама мыла раму'));
    }

    public function testUuid()
    {
        $this->assertInstanceOf(UuidInterface::class, Str::uuid());
        $this->assertInstanceOf(UuidInterface::class, Str::orderedUuid());
    }
}

class StringableObjectStub
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
