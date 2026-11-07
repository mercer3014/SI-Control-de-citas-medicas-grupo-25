<?php

namespace App\Http\Controllers;

use App\Models\UsuarioModel;
use App\Services\BitacoraService;
use App\Services\ResendService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request, ResendService $resend)
    {
        $request->validate([
            'email' => 'required|email|exists:usuarios,email',
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'El formato del correo es inválido.',
            'email.exists' => 'No encontramos un usuario con este correo electrónico.',
        ]);

        $usuario = UsuarioModel::where('email', $request->email)->first();

        // Generar código numérico de 6 dígitos para facilidad de uso
        $token = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        // Persistir también el código en la ficha de seguridad del usuario
        $usuario->generarCodigoRecuperacion($token);

        $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #0F2942; text-align: center;'>Clínica Vida Salud</h2>
                <p>Hola, <strong>{$usuario->persona->nombres}</strong>.</p>
                <p>Has solicitado restablecer tu contraseña. Utiliza el siguiente código de verificación:</p>
                <div style='background-color: #f1f5f9; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 4px; color: #2563EB; border-radius: 6px; margin: 20px 0;'>
                    {$token}
                </div>
                <p style='color: #64748b; font-size: 13px;'>Este código expirará en 15 minutos. Si no realizaste esta solicitud, puedes ignorar este mensaje.</p>
            </div>
        ";

        $resend->sendEmail($request->email, 'Recuperación de contraseña - Clínica Vida Salud', $htmlContent);

        BitacoraService::registrar(
            'SOLICITUD_RECUPERACION',
            'Seguridad',
            "Se envió código de recuperación al correo {$request->email}",
            $usuario->id
        );

        return redirect()->route('password.reset', ['email' => $request->email])
            ->with('success', 'Te hemos enviado un código de recuperación a tu correo.');
    }

    public function showResetForm(Request $request)
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email ?? '',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:usuarios,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'token.required' => 'Ingresa el código de 6 dígitos que llegó a tu correo.',
            'password.required' => 'Ingresa la nueva contraseña.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', trim($request->token))
            ->first();

        if (!$resetRecord) {
            return back()->withErrors(['token' => 'El código de verificación es incorrecto.']);
        }

        // Validar expiración de 15 minutos
        if (Carbon::parse($resetRecord->created_at)->addMinutes(15)->isPast()) {
            return back()->withErrors(['token' => 'El código ha expirado. Por favor solicita uno nuevo.']);
        }

        $usuario = UsuarioModel::where('email', $request->email)->first();
        $usuario->password = Hash::make($request->password);
        // Restablecer la contraseña también revierte cualquier nivel de bloqueo
        $usuario->desbloquear();
        $usuario->limpiarCodigoRecuperacion();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        BitacoraService::registrar(
            'CAMBIO_PASSWORD',
            'Seguridad',
            "Contraseña restablecida exitosamente para el usuario {$usuario->username}",
            $usuario->id
        );

        return redirect()->route('login')
            ->with('success', 'Tu contraseña ha sido restablecida. Ya puedes iniciar sesión.');
    }
}
