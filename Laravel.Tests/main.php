<?php

/*
* This file is part of PHPUnit.
*
* (c) Sebastian Bergmann <sebastian@phpunit.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

// require_once(__DIR__ . "/vendor/laravel_autoload.php");
// require_once(__DIR__ . "/vendor/autoload.php");

// function main()
// {
//     if (!ini_get('date.timezone')) {
//         ini_set('date.timezone', 'UTC');
//     }

//     $options = getopt('', array('prepend:'));
//     if (isset($options['prepend'])) {
//         require $options['prepend'];
//     }
//     unset($options);

//     PHPUnit\TextUI\Command::main();
// }

// main();

class TestIteratorAggregate implements \IteratorAggregate
{
    public function getIterator() {
        return new \ArrayIterator($this);
    }
}

$tmp = iterator_to_array(new TestIteratorAggregate());