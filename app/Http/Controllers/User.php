<?php

namespace App\Http\Controllers;

use App\Models\Insurance\Insurer;
use App\Utils\Response as Response;
use Illuminate\Http\Request;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class User extends Controller
{
    /**
     * Authenticate user and return profile data.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            if ($request->input('login_method') === 'google_login') {
                return $this->googleLogin($request);
            }

            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string']
            ]);

            if ($validator->fails()) {
                return Response::validationFailed($validator->errors());
            }

            $user = UserModel::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return Response::failed('Invalid email or password.', 401);
            }

            if ($user->status !== 'active') {
                return Response::failed(sprintf('Your account is %s.', $user->status), 403, [], 403);
            }

            return Response::success(
                array(
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                    'status' => $user->status
                )
            );
        } catch (\Throwable $th) {
            return Response::failed(
                'Internal server error.',
                500,
                array('message' => $th->getMessage()),
                500
            );
        }
    }

    /**
     * Verify Google ID token and return the matching user profile.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_token' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return Response::validationFailed($validator->errors());
            }
            \Log::info('Received Google login request', ['id_token' => $request->id_token]);
            $googleUser = $this->verifyGoogleIdToken($request->id_token);
            \Log::info('Google token verification result', ['google_user' => $googleUser]);
            if (!$googleUser) {
                return Response::failed('Invalid Google token.', 401);
            }

            if (!filter_var((string) ($googleUser['email_verified'] ?? false), FILTER_VALIDATE_BOOLEAN)) {
                return Response::failed('Google account email is not verified.', 403, [], 403);
            }

            $user = UserModel::where('email', $googleUser['email'] ?? '')->first();

            if (!$user) {
                return Response::failed('User not found.', 404, [], 404);
            }

            if ($user->status !== 'active') {
                return Response::failed(sprintf('Your account is %s.', $user->status), 403, [], 403);
            }

            $insurer = Insurer::where('user_id', $user->id)->first();

            return Response::success(
                array(
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                    'status' => $user->status,
                    'insurer' => $insurer,
                    'google' => [
                        'sub' => $googleUser['sub'] ?? null,
                        'picture' => $googleUser['picture'] ?? null,
                    ],
                )
            );
        } catch (\Throwable $th) {
            return Response::failed(
                'Internal server error.',
                500,
                array('message' => $th->getMessage()),
                500
            );
        }
    }

    protected function verifyGoogleIdToken(string $idToken): ?array
    {
        $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            return null;
        }

        $configuredClientId = (string) config('services.google.client_id', '');
        $tokenAudience = (string) ($payload['aud'] ?? '');

        if ($configuredClientId !== '' && $tokenAudience !== $configuredClientId) {
            return null;
        }

        return $payload;
    }
}
