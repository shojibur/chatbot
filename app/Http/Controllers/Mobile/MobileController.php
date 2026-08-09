<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\KnowledgeSource;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class MobileController extends Controller
{
    protected function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user || $user->user_type !== User::TYPE_CLIENT) {
            throw ValidationException::withMessages([
                'email' => ['This mobile app is only available for client portal users.'],
            ]);
        }

        if (! $user->client_id || ! $user->client) {
            throw ValidationException::withMessages([
                'email' => ['Your account has not been linked to a client yet.'],
            ]);
        }

        return $user;
    }

    protected function currentClient(Request $request): Client
    {
        return $this->currentUser($request)->client;
    }

    protected function transformLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'contact' => $lead->contact,
            'user_request' => $lead->user_request,
            'trigger' => $lead->trigger,
            'status' => $lead->status,
            'notes' => $lead->notes,
            'chat_session_id' => $lead->chat_session_id,
            'created_at' => $lead->created_at?->toISOString(),
        ];
    }

    protected function transformLeadDetail(Lead $lead): array
    {
        return [
            ...$this->transformLead($lead),
            'conversation_snapshot' => $lead->conversation_snapshot,
        ];
    }

    protected function transformKnowledgeSource(KnowledgeSource $source): array
    {
        return [
            'id' => $source->id,
            'title' => $source->title,
            'source_type' => $source->source_type,
            'status' => $source->status,
            'source_url' => $source->source_url,
            'file_name' => $source->file_name,
            'token_estimate' => $source->token_estimate,
            'chunk_count' => $source->chunk_count,
            'processing_error' => $source->processing_error,
            'last_synced_at' => $source->last_synced_at?->toISOString(),
            'created_at' => $source->created_at?->toISOString(),
        ];
    }

    protected function transformPlan(?Plan $plan): ?array
    {
        if (! $plan) {
            return null;
        }

        return [
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
            'is_active' => (bool) $plan->is_active,
        ];
    }
}
