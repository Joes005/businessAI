<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponse;

class EnsureHasBusiness
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        if (!$user->current_business_id) {
            $firstBusiness = $user->businesses()->first();
            if ($firstBusiness) {
                $user->update(['current_business_id' => $firstBusiness->id]);
                $user->current_business_id = $firstBusiness->id;
            } else {
                return $this->errorResponse('No active business found. Please complete business setup first.', 428, [
                    'requires_business_setup' => true
                ]);
            }
        }

        return $next($request);
    }
}
