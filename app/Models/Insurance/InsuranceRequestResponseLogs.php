<?php

namespace App\Models\Insurance;

use MongoDB\Laravel\Eloquent\Model;

class InsuranceRequestResponseLogs extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'insurance_reuest_response_logs';



    public static function insuranceRRLogs($request, $response, $action, $actionId = "")
    {
        $irrlObj = new InsuranceRequestResponseLogs();
        $irrlObj->action = $action;
        $irrlObj->action_id = $actionId;
        $irrlObj->request = $request;
        $irrlObj->response = $response;
        $irrlObj->save();
    }
}
