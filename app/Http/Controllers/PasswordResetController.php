<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function solicitar(): View
    {
        return view('auth.olvide-password');
    }

    public function enviarEnlace(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Respuesta idéntica exista o no el correo: si no, este formulario
        // sirve para averiguar qué cuentas están registradas.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Si el correo está registrado, te mandamos un enlace para restablecer tu contraseña.');
    }

    public function formulario(string $token): View
    {
        return view('auth.restablecer-password', [
            'token' => $token,
            'email' => request('email', ''),
        ]);
    }

    public function restablecer(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($usuario, $password) {
                // el cast 'hashed' del modelo User se encarga de aplicar el hash.
                $usuario->forceFill(['password' => $password])->save();
                $usuario->cerrarSesionesActivas();

                event(new PasswordReset($usuario));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withErrors(['email' => 'El enlace de restablecimiento no es válido o ya expiró.'])
                ->onlyInput('email');
        }

        // si quien restableció ya tenía una sesión activa (propia vieja, o de
        // otra cuenta en el mismo navegador), la cerramos: si no, /login la
        // hubiera rebotado en silencio por el middleware "guest".
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->with('status', 'Tu contraseña se actualizó. Iniciá sesión de nuevo.');
    }
}
