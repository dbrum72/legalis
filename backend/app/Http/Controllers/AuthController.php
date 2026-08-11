<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:api', except: ['login']),
        ];
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token);
        }

        return response()->json([
            'msg' => 'Usuário e/ou senha inválidos.',
        ], 403);
    }

    public function me(): JsonResponse
    {
        return response()->json(
            $this->guard()->user()
        );
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json([
            'msg' => 'Desconectado com sucesso.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(
            $this->guard()->refresh()
        );
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        $user = $this->guard()->user();

        return response()->json([
            'token' => $token,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,

            'userName' => $user->name,
            'userMail' => $user->email,
            'user' => $user->toArray(),

            'roles' => $user->getRoleNames(),
            'permissions' => $user
                ->getAllPermissions()
                ->pluck('name'),
        ]);
    }

    protected function guard()
    {
        return Auth::guard('api');
    }
}