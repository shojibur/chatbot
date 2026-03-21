<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans = Plan::query()
            ->withCount('clients')
            ->orderBy('price_monthly')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'monthly_token_limit' => $plan->monthly_token_limit,
                'monthly_message_limit' => $plan->monthly_message_limit,
                'max_knowledge_sources' => $plan->max_knowledge_sources,
                'max_file_upload_mb' => $plan->max_file_upload_mb,
                'features' => $plan->features ?? [],
                'is_active' => $plan->is_active,
                'clients_count' => $plan->clients_count,
                'created_at' => $plan->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('plans/Index', [
            'plans' => $plans,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('plans/Create', [
            'plan' => [
                'id' => null,
                'name' => '',
                'slug' => '',
                'description' => '',
                'price_monthly' => 0,
                'monthly_token_limit' => 100000,
                'monthly_message_limit' => 200,
                'max_knowledge_sources' => 5,
                'max_file_upload_mb' => 10,
                'features' => [],
                'is_active' => true,
            ],
        ]);
    }

    public function store(UpdatePlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Plan::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'price_monthly' => $validated['price_monthly'],
            'monthly_token_limit' => $validated['monthly_token_limit'],
            'monthly_message_limit' => $validated['monthly_message_limit'],
            'max_knowledge_sources' => $validated['max_knowledge_sources'],
            'max_file_upload_mb' => $validated['max_file_upload_mb'],
            'features' => array_values(array_filter($validated['features'] ?? [])),
            'is_active' => $validated['is_active'],
        ]);

        return to_route('plans.index')->with('status', 'plan-created');
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description ?? '',
                'price_monthly' => (float) $plan->price_monthly,
                'monthly_token_limit' => $plan->monthly_token_limit,
                'monthly_message_limit' => $plan->monthly_message_limit,
                'max_knowledge_sources' => $plan->max_knowledge_sources,
                'max_file_upload_mb' => $plan->max_file_upload_mb,
                'features' => $plan->features ?? [],
                'is_active' => $plan->is_active,
            ],
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validated();

        $plan->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price_monthly' => $validated['price_monthly'],
            'monthly_token_limit' => $validated['monthly_token_limit'],
            'monthly_message_limit' => $validated['monthly_message_limit'],
            'max_knowledge_sources' => $validated['max_knowledge_sources'],
            'max_file_upload_mb' => $validated['max_file_upload_mb'],
            'features' => array_values(array_filter($validated['features'] ?? [])),
            'is_active' => $validated['is_active'],
        ]);

        return to_route('plans.index')->with('status', 'plan-updated');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->clients()->exists()) {
            return to_route('plans.index')->with('status', 'plan-has-clients');
        }

        $plan->delete();

        return to_route('plans.index')->with('status', 'plan-deleted');
    }
}
