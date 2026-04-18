<?php

namespace App\Http\Responses;

use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->user_type === User::TYPE_ADMIN) {
            return redirect()->intended(route('dashboard'));
        }

        if ($user->user_type === User::TYPE_CLIENT) {
            return redirect()->intended(route('portal.dashboard'));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
