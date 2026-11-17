<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // UC-01: data usaha (profil usaha) dikumpulkan sekaligus saat register.
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
        ]);

        // role/plan tidak ikut dari input: user baru selalu free (anti privilege escalation).
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->businessProfile()->create([
            'business_name' => $data['business_name'],
            'business_type' => $data['business_type'] ?? null,
            'city' => $data['city'] ?? null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('consultation.index');
    }
}
