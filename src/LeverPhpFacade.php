<?php

namespace ViaWork\LeverPhp;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ViaWork\LeverPhp\Lever
 */
class LeverPhpFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'lever';
    }
}
