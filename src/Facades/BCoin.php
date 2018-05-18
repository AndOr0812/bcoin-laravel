<?php

namespace TPenaranda\BCoin\Facades;

use Illuminate\Support\Facades\Facade;

class BCoin extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'tpenaranda-bcoin-laravel';
    }
}
