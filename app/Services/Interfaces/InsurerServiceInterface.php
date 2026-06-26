<?php

namespace App\Services\Interfaces;

interface InsurerServiceInterface
{
    public function createInsurer(array $payload): array;
    public function updateInsurer(array $payload, int $id): array;
    public function getInsurerByUserId(int $userId): array;
}
