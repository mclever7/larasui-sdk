<?php 

namespace Mclever\LarasuiSdk\Facades;

use Illuminate\Support\Facades\Facade;

class Sui extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sui';
    }
}
