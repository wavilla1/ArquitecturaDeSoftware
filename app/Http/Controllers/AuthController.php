<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth', ['register' => false, 'activeUser' => null]);
    }

    public function registerForm()
    {
        return view('auth', ['register' => true, 'activeUser' => null]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('profile'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'handle' => ['required', 'regex:/^[a-z0-9_]{3,24}$/', 'unique:users,handle'],
            'email' => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(10)],
        ]);
        $user = User::create($data + ['balance' => 25, 'accent' => '#7c3aed']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile')->with('success', 'Tu cuenta está lista. Recibiste 25 MONO virtuales para explorar el mercado.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('marketplace');
    }
}
