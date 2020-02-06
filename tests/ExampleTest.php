<?php

namespace ViaWork\LeverPhp\Tests;

use Orchestra\Testbench\TestCase;
use ViaWork\LeverPhp\LeverPhpServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LeverPhpServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
