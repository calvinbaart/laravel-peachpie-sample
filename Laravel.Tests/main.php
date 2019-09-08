<?php

/*
* This file is part of PHPUnit.
*
* (c) Sebastian Bergmann <sebastian@phpunit.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

if (!defined("PEACHPIE_VERSION")) {
    require_once(__DIR__ . "/../Laravel/vendor/laravel_autoload.php");
} else {
    require_once(__DIR__ . "/vendor/laravel_autoload.php");
}

require_once(__DIR__ . "/vendor/autoload.php");

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

$options = getopt('', array('prepend:'));
if (isset($options['prepend'])) {
    require $options['prepend'];
}
unset($options);

PHPUnit\TextUI\Command::main();