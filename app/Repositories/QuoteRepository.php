<?php

namespace App\Repositories;

use App\Repositories\Interfaces\QuoteRepositoryInterface;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class QuoteRepository implements QuoteRepositoryInterface
{
    private Manager $manager;
    private string $database;

    public function __construct()
    {
        $host = config('database.connections.mongodb.host', env('MONGODB_HOST', '127.0.0.1'));
        $port = (int) config('database.connections.mongodb.port', env('MONGODB_PORT', 27018));
        $username = config('database.connections.mongodb.username', env('MONGODB_USERNAME', ''));
        $password = config('database.connections.mongodb.password', env('MONGODB_PASSWORD', ''));
        $this->database = config('database.connections.mongodb.database', env('MONGODB_DATABASE', 'drm_insurance'));
        // Build the authenticated connection URI pointing to the admin auth database
        $uri = sprintf(
            'mongodb://%s:%s@%s:%d/admin',
            rawurlencode($username),
            rawurlencode($password),
            $host,
            $port
        );
        $this->manager = new Manager($uri);
    }

    public function saveQuote(array $data): string
    {
        $bulk = new BulkWrite();
        $objectId = new ObjectId();

        $document = [
            '_id'                      => $objectId,
            'insurer_id'               => $data['insurer_id'] ?? null,
            'proposer_type'            => $data['proposer_type'] ?? null,
            'proposer_pincode'         => $data['proposer_pincode'] ?? null,
            'vehicle_make'             => $data['vehicle_make'] ?? null,
            'vehicle_model'            => $data['vehicle_model'] ?? null,
            'vehicle_variant'          => $data['vehicle_variant'] ?? null,
            'man_year'                 => $data['man_year'] ?? null,
            'price_offered'            => $data['price_offered'] ?? 0,
            'status'                   => $data['status'] ?? 'failed',
            'request_payload'          => $data['request_payload'] ?? [], // Complete request payload
            'insurer_response_payload' => $data['insurer_response_payload'] ?? [], // Complete response payload
            'created_at'               => new UTCDateTime((int)(round(microtime(true) * 1000))),
        ];
        $bulk->insert($document);

        // Execute the write operation on the target database and collection
        $this->manager->executeBulkWrite("{$this->database}.insurance_quotes", $bulk);
        return (string) $objectId;
    }
}
