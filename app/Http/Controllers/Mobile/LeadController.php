<?php

namespace App\Http\Controllers\Mobile;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        $client = $this->currentClient($request);

        $paginated = $client->leads()
            ->latest()
            ->paginate(20);

        return response()->json([
            'leads' => $paginated->map(fn ($lead) => $this->transformLead($lead)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'has_more'     => $paginated->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, Lead $lead): JsonResponse
    {
        $client = $this->currentClient($request);
        abort_unless($lead->client_id === $client->id, 403);

        return response()->json([
            'lead' => $this->transformLeadDetail($lead),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $client = $this->currentClient($request);
        abort_unless($lead->client_id === $client->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,closed'],
        ]);

        $lead->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'lead' => $this->transformLeadDetail($lead->fresh()),
        ]);
    }
}
