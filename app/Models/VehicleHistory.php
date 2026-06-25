<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class VehicleHistory extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'vehicle_history';


    public function createVehicleHistory($rc_number, $data){
        $this->rc_number = strtolower($rc_number);
        $this->response = $data;
        $this->save();
    }
}
