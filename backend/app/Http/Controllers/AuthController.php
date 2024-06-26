<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    /**
     * Register a user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     *
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);
        $data['username'] = strstr($data['email'], '@', true);

        $user = User::create($data);

        $token = $user->createToken(User::USER_TOKEN);

        return $this->success([
            'token' => $token->plainTextToken,
            'user' => $user,
        ], 'Register successfully');
    }

    /**
     * Login a user
     *
     * @param LoginRequest $request
     * @return JsonResponse
     *
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $isValidate = $this->isValidCredentials($request);

        if (!$isValidate['success']) {
            return $this->error($isValidate['message'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $isValidate['user'];
        $token = $user->createToken(User::USER_TOKEN);

        return $this->success([
            'token' => $token->plainTextToken,
            'user' => $user,
        ], 'Login successfully');
    }

    /**
     * Validate user credentials
     *
     * @param LoginRequest $request
     * @return array
     *
     */
    private function isValidCredentials(LoginRequest $request): array
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if ($user === null) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        if (Hash::check($data['password'], $user->password)) {
            return [
                'success' => true,
                'user' => $user,
            ];
        }

        return [
            'success' => false,
            'message' => 'Password not match',
        ];
    }

    /**
     * Login with a user
     *
     * @return JsonResponse
     *
     */
    public function loginWithToken(): JsonResponse
    {
        return $this->success(auth()->user(), 'Login successfully');
    }

    /**
     * Logout a user
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logout successfully');
    }
}
