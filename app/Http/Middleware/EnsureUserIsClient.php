<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not authenticated at all → send to login
        if (! $user) {
            return redirect()->route('login');
        }

        // Authenticated admin trying to hit the portal → send to admin dashboard
        if ($user->user_type === User::TYPE_ADMIN) {
            return redirect()->route('dashboard');
        }

        // Is a client user but has no client account assigned yet
        if ($user->user_type === User::TYPE_CLIENT && ! $user->client_id) {
            // Log them out so they're not stuck in a broken state
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has not been linked to a client yet. Please contact your administrator.',
            ]);
        }

        // Fully valid client user
        if ($user->user_type === User::TYPE_CLIENT && $user->client_id) {
            return $next($request);
        }

        // Any other unknown type → back to login
        return redirect()->route('login');
    }
}
