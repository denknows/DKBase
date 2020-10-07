<?php

namespace Denknows\DKBase\Tests;

use Orchestra\Testbench\TestCase;
use Denknows\DKBase\DKBaseServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [DKBaseServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
