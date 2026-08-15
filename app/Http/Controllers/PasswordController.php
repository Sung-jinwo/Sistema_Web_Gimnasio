<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class PasswordController extends Controller
{
    /**
     * Mostrar formulario de recuperación de contraseña
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Enviar enlace de recuperación de contraseña
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'Debe ingresar un correo electrónico válido',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($request->expectsJson()) {
            return $status === Password::RESET_LINK_SENT
                ? response()->json(['message' => 'Se ha enviado el enlace de recuperación a su correo electrónico'])
                : response()->json(['message' => 'No se pudo enviar el enlace de recuperación'], 422);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Se ha enviado el enlace de recuperación a su correo electrónico')
            : back()->withErrors(['email' => 'No se pudo enviar el enlace de recuperación']);
    }

    /**
     * Mostrar formulario de reset de contraseña
     */
    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', ['request' => $request, 'token' => $token]);
    }

    /**
     * Procesar el reset de contraseña
     */
    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'token.required' => 'Token inválido',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'Debe ingresar un correo electrónico válido',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($request->expectsJson()) {
            return $status === Password::PASSWORD_RESET
                ? response()->json(['message' => 'Contraseña restablecida exitosamente'])
                : response()->json(['message' => 'No se pudo restablecer la contraseña'], 422);
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Contraseña restablecida exitosamente')
            : back()->withErrors(['email' => 'No se pudo restablecer la contraseña']);
    }

    /**
     * Mostrar formulario de cambio de contraseña para usuarios autenticados
     */
    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    /**
     * Procesar el cambio de contraseña para usuarios autenticados
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria',
            'password.required' => 'La nueva contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'La contraseña actual es incorrecta'], 422);
            }

            return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contraseña cambiada exitosamente']);
        }

        return back()->with('status', 'Contraseña cambiada exitosamente');
    }
}
