<?php

namespace Illuminate\Tests\View\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

class BladeIfAuthStatementsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@auth("api")
breeze
@endauth';
        $expected = '<?php if(auth()->guard("api")->check()): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testPlainIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@auth
breeze
@endauth';
        $expected = '<?php if(auth()->guard()->check()): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock(Filesystem::class);
    }
}
