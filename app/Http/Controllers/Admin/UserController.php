<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('client')
            ->latest()
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'client_name' => $user->client?->name,
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('users/Index', [
            'users' => $users,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'user_types' => [User::TYPE_ADMIN, User::TYPE_CLIENT],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('users/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'user_types' => [User::TYPE_ADMIN, User::TYPE_CLIENT],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'client_id' => $request->user_type === User::TYPE_CLIENT ? $request->client_id : null,
        ]);

        return to_route('users.index')->with('status', 'user-created');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'client_id' => $user->client_id,
            ],
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'user_types' => [User::TYPE_ADMIN, User::TYPE_CLIENT],
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'user_type' => $request->user_type,
            'client_id' => $request->user_type === User::TYPE_CLIENT ? $request->client_id : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return to_route('users.index')->with('status', 'user-updated');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('status', 'error-cannot-delete-self');
        }

        $user->delete();

        return to_route('users.index')->with('status', 'user-deleted');
    }
}
