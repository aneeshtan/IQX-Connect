<?php

namespace App\Http\Controllers;

use App\Models\SheetSource;
use App\Services\WordPressLeadWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SourceWebhookController extends Controller
{
    public function ingest(Request $request, SheetSource $source, WordPressLeadWebhookService $service): JsonResponse
    {
        $token = $request->header('X-IQX-Source-Token')
            ?: $request->string('token')->toString()
            ?: $request->input('token');

        try {
            $lead = $service->ingest($source, $request->all(), $token);

            return response()->json([
                'status' => 'ok',
                'source_id' => $source->id,
                'lead_id' => $lead->id,
            ]);
        } catch (\RuntimeException $exception) {
            $status = $exception->getMessage() === 'Invalid source token.'
                ? Response::HTTP_FORBIDDEN
                : Response::HTTP_UNPROCESSABLE_ENTITY;

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], $status);
        }
    }
}
