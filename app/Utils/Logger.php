<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Logger
{
    public static function logException($exception, $code = null, array $extra = [])
    {
        try {

            $isThrowable = $exception instanceof \Throwable;

            $message = $isThrowable
                ? $exception->getMessage()
                : (string) $exception;

            $level = self::getLogLevel($exception, $code);

            $baseContext = [
                'url' => !empty($extra['url']) ? $extra['url'] : request()->fullUrl(),
                'method' => !empty($extra['method']) ? $extra['method'] : request()->method(),
                'extra' => $extra,
                'uuid' => (string) Str::uuid(),
                'timestamp' => now()->toDateTimeString(),
            ];

            /**
             * 1. SHORT LOG (NO TRACE)
             */
            Log::channel('insure_error')->log($level, $message, [
                'message' => $message,
                'url' => $baseContext['url'],
                'method' => $baseContext['method'],
                'uuid' => $baseContext['uuid'],
                'timestamp' => $baseContext['timestamp'],
            ]);

            /**
             * 2. DETAILED LOG (WITH TRACE)
             */
            $detailedContext = $baseContext + [
                'request' => [
                    'input' => request()->all(),
                    'json' => request()->json()->all() ?? [],
                ],

                'meta' => [
                    'route' => Route::currentRouteAction(),
                    'ip' => request()->ip(),
                    'referer' => request()->header('referer'),
                    'hostname' => gethostname(),
                ],

                'exception' => $isThrowable ? [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                ] : [],
            ];

            Log::channel('insure_detail_error')->log($level, $message, $detailedContext);

        } catch (\Throwable $e) {
            Log::error('Logger failed: ' . $e->getMessage());
        }
    }

    private static function getLogLevel($exception, $code = null)
    {
        $isThrowable = $exception instanceof \Throwable;

        if ($code == -1 || ($isThrowable && $exception->getCode() == -1)) {
            return 'info';
        }

        if ($code == -2 || ($isThrowable && $exception->getCode() == -2)) {
            return 'debug';
        }

        return 'error';
    }

    public static function log($message)
    {
        try {
            Log::info($message);
        } catch (\Throwable $th) {
            self::logException($th);
        }
    }
}