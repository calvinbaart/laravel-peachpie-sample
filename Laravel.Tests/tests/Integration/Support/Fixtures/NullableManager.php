<?php

namespace Illuminate\Tests\Integration\Support\Fixtures;

use Illuminate\Support\Manager;

class NullableManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return null;
    }
}
