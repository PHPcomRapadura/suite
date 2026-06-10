<?php

namespace App\Http\Controllers\Cfp;

use App\Http\Controllers\Controller;
use App\Mail\CfpResetPasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class CfpPasswordResetController extends Controller
{
    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        Password::broker()->sendResetLink(
            $request->only('email'),
            function (User $user, string $token) {
                Mail::to($user->email)->send(new CfpResetPasswordMail($user, $token));
            }
        );

        // Sempre retorna 200 para evitar enumeração de e-mails
        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá o link de redefinição em instantes.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => match ($status) {
                    Password::INVALID_TOKEN => 'Link inválido ou expirado. Solicite um novo.',
                    Password::INVALID_USER => 'Não encontramos uma conta com esse e-mail.',
                    default => 'Não foi possível redefinir a senha. Tente novamente.',
                },
            ], 422);
        }

        return response()->json(['message' => 'Senha redefinida com sucesso. Você já pode fazer login.']);
    }
}
