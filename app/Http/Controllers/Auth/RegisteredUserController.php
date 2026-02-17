<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use App\Providers\RouteServiceProvider;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64', // 🔹 Límite antes de encriptar
                'confirmed',
                Password::min(8)
                    ->mixedCase() // 🔹 Debe contener mayúsculas y minúsculas
                    ->letters() // 🔹 Debe incluir letras
                    ->numbers() // 🔹 Debe incluir al menos un número
                    ->symbols(), // 🔹 Debe incluir al menos un símbolo especial
                'regex:/^[\w!@#$%^&*()-_+=<>?]+$/u', // 🔹 Bloquea caracteres no permitidos como emoticones
                'not_regex:/\s/', // 🔹 Bloquea espacios en cualquier parte
            ],
        ]);

        $user = User::create([
            'name' => htmlspecialchars($request->name, ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($request->email, ENT_QUOTES, 'UTF-8'),
            'password' => Hash::make(trim($request->password)),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
