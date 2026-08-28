<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\StoreBusinessRequest;
use App\Models\Business;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{
    use ApiResponse;

    /**
     * Get all businesses belonging to authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $businesses = $request->user()->businesses()->get();

        return $this->successResponse([
            'businesses'       => $businesses,
            'current_business' => $request->user()->currentBusiness,
        ], 'Businesses retrieved successfully.');
    }

    /**
     * Create a new business and assign owner.
     */
    public function store(StoreBusinessRequest $request): JsonResponse
    {
        $user = $request->user();

        $business = DB::transaction(function () use ($request, $user) {
            $data = $request->validated();
            if (empty($data['email'])) {
                $data['email'] = $user->email;
            }

            $business = Business::create($data);

            // Attach user as owner in pivot table
            $business->users()->attach($user->id, [
                'role'      => 'owner',
                'is_active' => true,
            ]);

            // Set as current active business
            $user->update(['current_business_id' => $business->id]);

            return $business;
        });

        return $this->successResponse([
            'business' => $business,
            'user'     => $user->fresh(['businesses', 'currentBusiness']),
        ], 'Business created successfully! Welcome to your digital business copilot.', 201);
    }

    /**
     * Switch active business context.
     */
    public function switchBusiness(Request $request, Business $business): JsonResponse
    {
        $user = $request->user();

        // Authorize membership
        $belongsToBusiness = $user->businesses()->where('businesses.id', $business->id)->exists();

        if (!$belongsToBusiness) {
            return $this->errorResponse('You do not have access to this business.', 403);
        }

        $user->update(['current_business_id' => $business->id]);

        return $this->successResponse([
            'current_business' => $business,
        ], "Switched active business to {$business->name}.");
    }
}
