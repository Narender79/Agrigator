<?php

namespace App\Utils;

use Illuminate\Http\JsonResponse;

class Response
{
    const CODE_SUCCESS = 'success';
    const CODE_FAILED = 'failed';
    const CODE_REDIRECT = 'redirect';

    /**
     * Format a success response.
     *
     * @param  mixed  $data
     * @param  array  $rest
     * @param  int  $status
     * @return JsonResponse
     */
    public static function success($data, string $message = "success", array $rest = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge([
            'code' => self::CODE_SUCCESS,
            'message' => $message,
            'data' => $data,
        ], $rest), $status);
    }

    /**
     * Format a failed response.
     *
     * @param  mixed  $errors
     * @param  int  $code
     * @param  array  $rest
     * @param  int  $status
     * @return JsonResponse
     */
    public static function failed($errors, int $code = 500, array $rest = [], int $status = 500): JsonResponse
    {
        return response()->json(array_merge([
            'code' => self::CODE_FAILED,
            'error_code' => $code,
            'errors' => $errors,
        ], $rest), $status);
    }

    /**
     * Format a validation failed response.
     *
     * @param  mixed  $errors
     * @param  int  $code
     * @param  int  $status
     * @return JsonResponse
     */
    public static function validationFailed($errors, int $code = 422, int $status = 422): JsonResponse
    {
        return response()->json([
            'code' => self::CODE_FAILED,
            'error_code' => $code,
            'message' => 'Validation error',
            'errors' => $errors,
        ], $status);
    }

    /**
     * Format a redirect response.
     *
     * @param  string  $url
     * @param  int  $status
     * @return JsonResponse
     */
    public static function redirect(string $url, int $status = 302): JsonResponse
    {
        return response()->json([
            'code' => self::CODE_REDIRECT,
            'url' => $url,
        ], $status);
    }

    public static function exeptionResponse($e, $code = null, array $extra = [])
    {
        Logger::logException($e, $code, $extra);
        return Response::failed($e->getMessage(), 500);
    }
}
