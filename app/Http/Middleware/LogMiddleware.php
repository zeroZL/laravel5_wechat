<?php

namespace App\Http\Middleware;

use Closure;
use App\Token;
use App\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache;

class LogMiddleware
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
        $response=$next($request);
        $logs=new Log();
        $token=Request::input('token');
        $token=Token::tokenIs($token)->first();
        $logs->token_id=$token->id;
        $logs->ip=$_SERVER['REMOTE_ADDR'];
        $access_token=Cache::get('access_token');
        $logs->result=$access_token;
        $logs->save();
        return $response;
    }
}
