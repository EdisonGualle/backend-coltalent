<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;



class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
       'XSRF-TOKEN',
    ];


}
