<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToBusiness
{
    /**
     * Boot the trait to add global business scope and auto-populate business_id.
     */
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business_scope', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                $businessId = $user->current_business_id;

                if (!$businessId && $user->businesses()->exists()) {
                    $firstBusiness = $user->businesses()->first();
                    if ($firstBusiness) {
                        $businessId = $firstBusiness->id;
                        $user->update(['current_business_id' => $businessId]);
                        $user->current_business_id = $businessId;
                    }
                }

                if ($businessId) {
                    $builder->where($builder->getModel()->getTable() . '.business_id', $businessId);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->business_id) && auth()->check()) {
                $user = auth()->user();
                $businessId = $user->current_business_id;

                if (!$businessId && $user->businesses()->exists()) {
                    $firstBusiness = $user->businesses()->first();
                    if ($firstBusiness) {
                        $businessId = $firstBusiness->id;
                        $user->update(['current_business_id' => $businessId]);
                        $user->current_business_id = $businessId;
                    }
                }

                $model->business_id = $businessId;
            }
        });
    }
}
