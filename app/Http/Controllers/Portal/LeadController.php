<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $client = $request->user()->client;

        $leads = $client->leads()
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('portal/Leads', [
            'leads'   => $leads->through(fn ($lead) => [
                'id'           => $lead->id,
                'name'         => $lead->name,
                'contact'      => $lead->contact,
                'user_request' => $lead->user_request,
                'trigger'      => $lead->trigger,
                'status'       => $lead->status,
                'notes'        => $lead->notes,
                'created_at'   => $lead->created_at?->toDateTimeString(),
            ]),
            'filters' => $request->only(['status']),
            'status'  => $request->session()->get('status'),
        ]);
    }

    public function show(Request $request, int $leadId): Response
    {
        $client = $request->user()->client;
        $lead   = $client->leads()->findOrFail($leadId);

        return Inertia::render('portal/LeadShow', [
            'lead' => [
                'id'                    => $lead->id,
                'name'                  => $lead->name,
                'contact'               => $lead->contact,
                'user_request'          => $lead->user_request,
                'trigger'               => $lead->trigger,
                'status'                => $lead->status,
                'notes'                 => $lead->notes,
                'created_at'            => $lead->created_at?->toDateTimeString(),
                'conversation_snapshot' => $lead->conversation_snapshot,
                'chat_session_id'       => $lead->chat_session_id,
            ],
        ]);
    }

    /**
     * Update lead status from the client portal.
     */
    public function updateStatus(Request $request, int $leadId)
    {
        $client = $request->user()->client;
        $lead   = $client->leads()->findOrFail($leadId);

        $lead->update($request->validate([
            'status' => 'required|in:new,contacted,closed',
        ]));

        return back();
    }

    /**
     * Delete a lead — scoped to the client's own data.
     */
    public function destroy(Request $request, int $leadId)
    {
        $client = $request->user()->client;
        $lead   = $client->leads()->findOrFail($leadId);
        $lead->delete();

        return back()->with('status', 'lead-deleted');
    }
}
