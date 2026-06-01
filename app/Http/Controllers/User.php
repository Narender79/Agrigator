<?php

namespace App\Http\Controllers;

use App\Utils\Response as Response;
use Illuminate\Http\Request;
use App\Models\User as UserModel;
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
}
