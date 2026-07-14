<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TataAigMasterService;
use App\Utils\Response;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Cache;

class RTO extends Controller
{
    protected string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = 'rto_data';
    }

    public function getRtoData(Request $request)
    {
        try {
            $rtoCode = $request->input('rto_code') ?? '';
            $rtoCity = $request->input('rto_city') ?? '';

            // Create a unique cache key based on the parameters
            $cacheKey = $this->cacheKey . '_' . md5(strtolower($rtoCode . '_' . $rtoCity));
            $cachedData = Cache::get($cacheKey);

            if($cachedData){
                return Response::success(data_get($cachedData,'datpa'));
            }

            $response = app(TataAigMasterService::class)->fetchRto($rtoCode, $rtoCity);

            if (data_get($response, 'status', 0) == 200) {

                // Cache RTO response for 30 days (in seconds)
                Cache::put($cacheKey, $response, 86400 * 30);
                return Response::success(data_get($response, 'data'));
            }

            $error = data_get($response, 'message_txt', null) ?? data_get($response, 'message', null) ?? data_get($response, 'errors', 'Something went wrong');

            return Response::failed($error);
        } catch (\Throwable $th) {
            return Response::exeptionResponse($th);
        }
    }
}