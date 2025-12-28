<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogViewerAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $username = env('LOG_VIEWER_USERNAME', 'admin');
        $password = env('LOG_VIEWER_PASSWORD');

        // If no password is set in .env, we'll use a default for testing 
        // OR you can keep it empty to require it to be set.
        if (empty($password)) {
            // For now, let's set a default if none provided, or just abort.
            // abort(403, 'Log Viewer password not set in environment.');
            $password = 'asip3000!'; 
        }

        if ($request->getUser() !== $username || $request->getPassword() !== $password) {
            return response('Unauthorized.', 401, [
                'WWW-Authenticate' => 'Basic realm="Log Viewer"',
            ]);
        }

        return $next($request);
    }
}

