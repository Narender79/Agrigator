<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TataAigMasterService;
use App\Utils\Response;
use Illuminate\Http\Request;

class RTO extends Controller
{


    public function getRtoData(Request $request)
    {
        try {
            $rtoCode = $request->input('rto_code') ?? '';
            $rtoCity = $request->input('rto_city') ?? '';

            $response = (new TataAigMasterService)->fetchRto($rtoCode, $rtoCity);

            if (data_get($response, 'status', 0) == 200) {
                return Response::success(data_get($response, 'data'));
            }

            $error = data_get($response, 'message_txt', null) ?? data_get($response, 'message', null) ?? data_get($response, 'errors', 'Something went wrong');

            return Response::failed($error);
        } catch (\Throwable $th) {
            return Response::exeptionResponse($th);
        }
    }
}