<?php

namespace app\http\middleware;

class Check
{
    public function handle($request, \Closure $next)
    {
        if ($request->param('name') == 'think') {
            return redirect('https://www.tinywan.com/');
        }
        return $next($request);
    }
}
