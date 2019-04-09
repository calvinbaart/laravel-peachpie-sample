<?php

/*
* This file is part of PHPUnit.
*
* (c) Sebastian Bergmann <sebastian@phpunit.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/*function main()
{
    if (!ini_get('date.timezone')) {
        ini_set('date.timezone', 'UTC');
    }

    foreach (array(__DIR__ . '/../../autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php') as $file) {
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

main();*/

$tmp1 = function (stdClass $user) {
	return true;
};

$tmp2 = function (?stdClass $user) {
	return true;
};

$param1 = (new ReflectionFunction($tmp1))->getParameters()[0];
$param2 = (new ReflectionFunction($tmp2))->getParameters()[0];

echo $param1->allowsNull() ? "true\r\n" : "false\r\n";
echo $param2->allowsNull() ? "true\r\n" : "false\r\n";