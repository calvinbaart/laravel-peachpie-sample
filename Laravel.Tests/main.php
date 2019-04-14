<?php

/*
* This file is part of PHPUnit.
*
* (c) Sebastian Bergmann <sebastian@phpunit.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

require_once(__DIR__ . "/vendor/laravel_autoload.php");
require_once(__DIR__ . "/vendor/autoload.php");

function main()
{
    if (!ini_get('date.timezone')) {
        ini_set('date.timezone', 'UTC');
    }

    $options = getopt('', array('prepend:'));
    if (isset($options['prepend'])) {
        require $options['prepend'];
    }
    unset($options);

    PHPUnit\TextUI\Command::main();
}

main();

/*class TestClass
{
}

interface TestInterface
{
}


echo class_exists(TestClass::class) ? "true" : "false";
echo interface_exists(TestClass::class) ? "true" : "false";
echo class_exists(TestInterface::class) ? "true" : "false";
echo interface_exists(TestInterface::class) ? "true" : "false";*/

/*namespace TestNamespace;
class Temp
{
}

function func1(): int
{
	return 10;
}

function func2(): void
{
}

function func3()
{
}

function func4()
{
	return 10;
}

function func5(): ?Temp
{
	return new Temp();
}

class TestClass
{
    function func1()
    {
    }

    function func2(): void
    {
    }

    function func3(): TestClass
    {
        return new TestClass();
    }

    function func4(): ?TestClass
    {
        return null;
    }
}

interface TestInterface
{
    function func1();
    function func2(): void;
    function func3(): TestClass;
    function func4(): ?TestClass;
}

$reflect1 = new \ReflectionFunction("TestNamespace\\func1");
$reflect2 = new \ReflectionFunction("TestNamespace\\func2");
$reflect3 = new \ReflectionFunction("TestNamespace\\func3");
$reflect4 = new \ReflectionFunction("TestNamespace\\func4");
$reflect5 = new \ReflectionFunction("TestNamespace\\func5");

$reflectClass1 = new \ReflectionClass("TestNamespace\\TestClass");
$reflectMethod1 = $reflectClass1->getMethod("func1");
$reflectMethod2 = $reflectClass1->getMethod("func2");
$reflectMethod3 = $reflectClass1->getMethod("func3");
$reflectMethod4 = $reflectClass1->getMethod("func4");

echo $reflect1->hasReturnType() ? "true" : "false";
echo $reflect1->getReturnType();
echo $reflect1->hasReturnType() && $reflect1->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflect2->hasReturnType() ? "true" : "false";
echo $reflect2->getReturnType();
echo $reflect2->hasReturnType() && $reflect2->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflect3->hasReturnType() ? "true" : "false";
echo $reflect3->getReturnType();
echo $reflect3->hasReturnType() && $reflect3->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflect4->hasReturnType() ? "true" : "false";
echo $reflect4->getReturnType();
echo $reflect4->hasReturnType() && $reflect4->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflect5->hasReturnType() ? "true" : "false";
echo $reflect5->getReturnType();
echo $reflect5->hasReturnType() && $reflect5->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflectMethod1->hasReturnType() ? "true" : "false";
echo $reflectMethod1->getReturnType();
echo $reflectMethod1->hasReturnType() && $reflectMethod1->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflectMethod2->hasReturnType() ? "true" : "false";
echo $reflectMethod2->getReturnType();
echo $reflectMethod2->hasReturnType() && $reflectMethod2->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflectMethod3->hasReturnType() ? "true" : "false";
echo $reflectMethod3->getReturnType();
echo $reflectMethod3->hasReturnType() && $reflectMethod3->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";

echo $reflectMethod4->hasReturnType() ? "true" : "false";
echo $reflectMethod4->getReturnType();
echo $reflectMethod4->hasReturnType() && $reflectMethod4->getReturnType()->allowsNull() ? "true" : "false";
echo "\r\n";*/

/*class Test1
{
    public function func1()
    {
    }

    public function func2()
    {
        return 10;
    }

    public function func3(): int
    {
        return 10;
    }
}

$tmp1 = new \ReflectionClass(Test1::class);
$method1 = $tmp1->getMethod("func1");
$method2 = $tmp1->getMethod("func2");
$method3 = $tmp1->getMethod("func3");

echo $method1->hasReturnType() ? "true" : "false";
echo $method2->hasReturnType() ? "true" : "false";
echo $method3->hasReturnType() ? "true" : "false";*/