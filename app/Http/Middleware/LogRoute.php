<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Log;

use Closure;

class LogRoute
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
        $response = $next($request);

        $log = [
            'URI' => $request->getUri(),
            'Method' => $request->getMethod(),
            'Request_Body' => $request->all(),
            'Response' => $response->getContent(),
        ];
        Log::info(json_encode($log));
        return $response;
    }
}
