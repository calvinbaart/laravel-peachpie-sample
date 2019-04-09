<?php

namespace Illuminate\Tests\View\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

class BladeIfGuestStatementsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@guest("api")
breeze
@endguest';
        $expected = '<?php if(auth()->guard("api")->guest()): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock(Filesystem::class);
    }
}
