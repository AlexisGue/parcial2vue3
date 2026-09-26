<?php

namespace App\Http\Controllers\Parcial;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Parcial2Vue3 — API Gestión Médica",
 *     version="1.0.0",
 *     description="API RESTful Laravel + Sanctum (tokens) para el Parcial II"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token"
 * )
 */
class AuthTokenController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Registro de usuario",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Usuario creado con token")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $token = $user->createToken('parcial2')->plainTextToken;

        return response()->json([
            'message' => 'Registro exitoso',
            'data' => [
                'user' => $this->userPayload($user),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login (paso 1) — genera reto 2FA",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"email","password"},
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Requiere verificación 2FA")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Correo o contraseña incorrectos.'],
            ]);
        }

        if (isset($user->is_active) && ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está desactivada.'],
            ]);
        }

        $challengeId = (string) Str::uuid();
        $code = (string) random_int(100000, 999999);

        Cache::put("parcial2fa:{$challengeId}", [
            'user_id' => $user->id,
            'code' => $code,
        ], now()->addMinutes(10));

        return response()->json([
            'message' => 'Se envió un código 2FA. (En demo el código viene en la respuesta.)',
            'data' => [
                'requires_2fa' => true,
                'challenge_id' => $challengeId,
                // Visible para demo/parcial (sin servicio de correo real).
                'demo_code' => $code,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/login/verify-2fa",
     *     tags={"Auth"},
     *     summary="Login (paso 2) — verifica 2FA y emite token",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"challenge_id","code"},
     *             @OA\Property(property="challenge_id", type="string"),
     *             @OA\Property(property="code", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Token emitido")
     * )
     */
    public function verify2fa(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $key = "parcial2fa:{$data['challenge_id']}";
        $payload = Cache::get($key);

        if (! $payload || (string) $payload['code'] !== (string) $data['code']) {
            throw ValidationException::withMessages([
                'code' => ['Código 2FA inválido o expirado.'],
            ]);
        }

        Cache::forget($key);

        /** @var User $user */
        $user = User::query()->findOrFail($payload['user_id']);
        $token = $user->createToken('parcial2')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada',
            'data' => [
                'user' => $this->userPayload($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     summary="Cerrar sesión (revoca token actual)",
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     summary="Usuario autenticado",
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->userPayload($request->user()),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
