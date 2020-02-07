<?php

namespace ViaWork\LeverPhp;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ViaWork\LeverPhp\LeverPhp
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
        return 'lever-php';
    }
}
