<?php

namespace ViaWork\LeverPhp\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ViaWork\LeverPhp\LeverPhp
 */
class Lever extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'lever-php';
    }
}
