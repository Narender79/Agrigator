<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'string', 'in:SUPER_ADMIN,PARTNER_ADMIN,PARTNER_AGENT,INSURER'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ])->validate();

        // Build create payload using validated fields plus any other allowed user attributes
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'mobile' => $validated['mobile'] ?? null,
            'role' => $validated['role'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ];

        // Additional optional user attributes that exist in the users table and are fillable
        $additionalFields = [
            'device_id', 'device_type', 'os_name', 'app_version', 'ip_address',
            'lat', 'long', 'browser_name', 'created_by', 'updated_by',
        ];

        foreach ($additionalFields as $field) {
            if ($request->filled($field) || $request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        $user = User::query()->create($data);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ], 201);
    }

    public function update(Request $request, $user)
    {
        // Resolve user manually so we can return a JSON 404 when missing
        $user = User::query()->find($user);
        if (! $user) {
            return response()->json([
                'code' => 'failed',
                'message' => 'User not found.',
            ], 404);
        }
        // Prevent updating immutable fields
        if ($request->has('email') || $request->has('mobile')) {
            return response()->json([
                'message' => 'email and mobile cannot be updated.',
            ], 422);
        }

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', 'string', 'in:SUPER_ADMIN,PARTNER_ADMIN,PARTNER_AGENT,INSURER'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive'])],
        ];

        // Optional additional fields
        $additionalFields = [
            'device_id', 'device_type', 'os_name', 'app_version', 'ip_address',
            'lat', 'long', 'browser_name', 'created_by', 'updated_by',
        ];

        foreach ($additionalFields as $field) {
            $rules[$field] = ['sometimes', 'nullable'];
        }

        $validated = Validator::make($request->all(), $rules)->validate();

        if (array_key_exists('password', $validated)) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Ensure additional fields sent are included
        foreach ($additionalFields as $field) {
            if ($request->filled($field) || $request->has($field)) {
                $validated[$field] = $request->input($field);
            }
        }

        $user->update($validated);

        return response()->json([
            'code' => 'success',
            'message' => 'User updated successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }
}
