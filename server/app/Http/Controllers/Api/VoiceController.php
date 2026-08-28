<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VoiceCommandService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceController extends Controller
{
    use ApiResponse;

    /**
     * Process spoken voice command transcript with language support.
     */
    public function process(Request $request, VoiceCommandService $voiceService): JsonResponse
    {
        $data = $request->validate([
            'transcript' => ['required', 'string', 'max:500'],
            'language'   => ['nullable', 'string', 'in:en,ta'],
        ]);

        $businessId = $request->user()->current_business_id;
        $language = $data['language'] ?? 'en';

        $result = $voiceService->processVoiceCommand($data['transcript'], $businessId, $language);

        return $this->successResponse($result, 'Voice command processed.');
    }
}
