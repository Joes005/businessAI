<?php

namespace App\Services\AI;

use App\Models\AiMemory;
use Carbon\Carbon;

class AIMemoryService
{
    /**
     * Store or update a business memory.
     */
    public function remember(int $businessId, string $key, string $value, string $category = 'business', ?int $ttlDays = null): AiMemory
    {
        $expiresAt = $ttlDays ? Carbon::now()->addDays($ttlDays) : null;

        return AiMemory::updateOrCreate(
            ['business_id' => $businessId, 'key' => $key],
            [
                'category'   => $category,
                'value'      => $value,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Retrieve non-expired business memories.
     */
    public function getActiveMemories(int $businessId, ?string $category = null): array
    {
        $query = AiMemory::where('business_id', $businessId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            });

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get()->map(function ($mem) {
            return [
                'key'      => $mem->key,
                'value'    => $mem->value,
                'category' => $mem->category,
            ];
        })->toArray();
    }

    /**
     * Delete memory by key.
     */
    public function forget(int $businessId, string $key): bool
    {
        return (bool) AiMemory::where('business_id', $businessId)->where('key', $key)->delete();
    }
}
