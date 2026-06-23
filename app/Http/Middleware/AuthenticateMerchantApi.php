<?php

namespace App\Http\Middleware;

use App\Models\PartnerCredential;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMerchantApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = (string) $request->header('X-Client-Id', $request->header('X-Api-Key', ''));
        $clientSecret = (string) $request->header('X-Client-Secret', $request->header('X-Api-Secret', ''));

        if ($clientId === '' || $clientSecret === '') {
            return response()->json([
                'success' => false,
                'message' => 'Merchant auth validation error',
                'errors'  => "Merchant credentials are required",
            ], 401);
        }

        $credential = PartnerCredential::query()
            ->where('api_key', $clientId)
            ->where('status', 'active')
            ->with('partner')
            ->first();

        if (! $credential || ! Hash::check($clientSecret, $credential->api_secret_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant auth validation error',
                'errors'  => "Invalid merchant credentials.",
            ], 401);
        }

        if (! $credential->partner || $credential->partner->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Merchant auth validation error',
                'errors'  => "Merchant account is not active.",
            ], 403);
        }

        $request->attributes->set('merchant_partner', $credential->partner);
        $request->attributes->set('merchant_credential', $credential);

        return $next($request);
    }
}