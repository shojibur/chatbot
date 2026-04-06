<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $leads = Lead::with('client')
            ->when($request->input('client_id'), fn ($q, $id) => $q->where('client_id', $id))
            ->when($request->input('status'),    fn ($q, $s)  => $q->where('status', $s))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('leads/Index', [
            'leads'   => $leads,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['client_id', 'status']),
        ]);
    }

    public function show(Lead $lead): Response
    {
        return Inertia::render('leads/Show', [
            'lead' => $lead->load('client', 'chatSession'),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status' => 'required|in:new,contacted,closed',
        ]);

        $lead->update(['status' => $data['status']]);

        return back();
    }
}
