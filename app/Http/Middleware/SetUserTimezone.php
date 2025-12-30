<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    /**
     * Set the application timezone based on the authenticated user's preference.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $timezone = $user->timezone ?? config('app.timezone');

            // Set the application timezone for this request
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }
}
