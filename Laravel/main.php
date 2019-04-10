<?php

/*
* This file is part of PHPUnit.
*
* (c) Sebastian Bergmann <sebastian@phpunit.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

function main()
{
    if (!ini_get('date.timezone')) {
        ini_set('date.timezone', 'UTC');
    }

    foreach (array(__DIR__ . '/../../laravel_autoload.php', __DIR__ . '/../vendor/laravel_autoload.php', __DIR__ . '/vendor/laravel_autoload.php') as $file) {
        if (file_exists($file)) {
            define('PHPUNIT_COMPOSER_INSTALL', $file);
            break;
        }
    }

    unset($file);
    if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
        fwrite(
            STDERR,
            'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL .
            '    composer install' . PHP_EOL . PHP_EOL .
            'You can learn all about Composer on https://getcomposer.org/.' . PHP_EOL
        );
        die(1);
    }

    $options = getopt('', array('prepend:'));
    if (isset($options['prepend'])) {
        require $options['prepend'];
    }
    unset($options);
    require PHPUNIT_COMPOSER_INSTALL;
    PHPUnit\TextUI\Command::main();
}

main();

// class Test1
// {
//     public function func1()
//     {
//     }

//     public function func2()
//     {
//         return 10;
//     }

//     public function func3(): int
//     {
//         return 10;
//     }
// }

// $tmp1 = new \ReflectionClass(Test1::class);
// $method1 = $tmp1->getMethod("func1");
// $method2 = $tmp1->getMethod("func2");
// $method3 = $tmp1->getMethod("func3");

// echo $method1->hasReturnType() ? "true" : "false";
// echo $method2->hasReturnType() ? "true" : "false";
// echo $method3->hasReturnType() ? "true" : "false";