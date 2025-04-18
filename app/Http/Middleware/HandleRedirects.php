<?php

// app/Http/Middleware/HandleRedirects.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Redirect;

class HandleRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $path = ltrim($request->path(), '/');

        $redirect = Redirect::query()->where('from_url', $path)->first();

        if ($redirect) {
            return redirect($redirect->to_url, 301);
        }

        return $next($request);
    }
}

