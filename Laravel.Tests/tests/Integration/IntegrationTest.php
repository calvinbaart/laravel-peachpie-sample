<?php

namespace Illuminate\Tests\Integration;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * @group integration
 */
class IntegrationTest extends TestCase
{
    public function test_simple_route_through_the_framework()
    {
        Route::get('/', function () {
            return 'Hello World';
        });

        $response = $this->get('/');

        $this->assertEquals('Hello World', $response->content());
    }
}
