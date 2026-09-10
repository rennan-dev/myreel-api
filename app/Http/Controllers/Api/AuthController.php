<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller {
    
    public function register(RegisterRequest $request): JsonResponse {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->username,
        ]);

        $token = $user->createToken('myreel_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse {
        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.'
            ], 401);
        }

        //revoga os tokens antigos por segurança
        $user->tokens()->delete();

        $token = $user->createToken('myreel_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(): JsonResponse {
        auth()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ], 200);
    }
}
