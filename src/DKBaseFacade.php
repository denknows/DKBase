<?php

namespace Denknows\DKBase;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Denknows\DKBase\Skeleton\SkeletonClass
 */
class DKBaseFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dkbase';
    }
}
