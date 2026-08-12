<?php

namespace App\Support;

use Illuminate\Support\Facades\Facade;

class Ui extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ui';
    }
}
