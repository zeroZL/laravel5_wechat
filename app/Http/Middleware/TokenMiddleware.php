<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Request;
use App\Token;
use League\Flysystem\Exception;

class TokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token=Request::input('token');
        if($token==''){
            throw new Exception('token can not be null');
        }
        $token=Token::tokenIs($token)->first();
        if(is_null($token)){
            throw new Exception('token not found');
        }
        return $next($request);
    }
}
