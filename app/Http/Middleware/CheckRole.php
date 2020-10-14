<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $currentUser = auth()->user();
        if($currentUser->tokenCan($role)){
            return $next($request);
        } else {
            return  response()->json(["error"=>"You are not authorized to access this route"],403);
        }
    }
}
