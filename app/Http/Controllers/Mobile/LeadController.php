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

        $leads = $client->leads()
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn ($lead) => $this->transformLead($lead));

        return response()->json([
            'leads' => $leads,
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
