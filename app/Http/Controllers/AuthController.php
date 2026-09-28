<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $claveLimite = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($claveLimite, 5)) {
            return back()
                ->withErrors(['email' => 'Demasiados intentos. Probá de nuevo en '.RateLimiter::availableIn($claveLimite).' segundos.'])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('recordar'))) {
            RateLimiter::hit($claveLimite, 60);

            return back()
                ->withErrors(['email' => 'Las credenciales no coinciden con ningún registro.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($claveLimite);
        $request->session()->regenerate();

        $usuario = $request->user();
        $destino = $usuario->role === RolUsuario::Administrador
            ? route('dashboard')
            : route('intentos.index');

        return redirect()->intended($destino)
            ->with('status', 'Sesión iniciada como '.$usuario->name.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada.');
    }
}
