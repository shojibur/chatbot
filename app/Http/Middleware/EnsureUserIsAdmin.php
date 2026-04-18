<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in → login page
        if (! $user) {
            return redirect()->route('login');
        }

        // Admin → allow through
        if ($user->user_type === User::TYPE_ADMIN) {
            return $next($request);
        }

        // Client user trying to hit admin routes → redirect to their portal
        if ($user->user_type === User::TYPE_CLIENT && $user->client_id) {
            return redirect()->route('portal.dashboard');
        }

        // Any other case → login
        return redirect()->route('login');
    }
}
