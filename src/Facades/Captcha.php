<?php

namespace Aron\Captcha\Facades;

use Illuminate\Support\Facades\Facade;

class Captcha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'aron-captcha'; // 👈 باید با نام register شده در ServiceProvider مطابقت داشته باشد
    }
}
