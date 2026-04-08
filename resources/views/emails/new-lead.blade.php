<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Lead Captured</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { width: 100%; padding: 40px 0; }
        .card { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background-color: #111827; padding: 24px 32px; }
        .header h1 { margin: 0; color: #ffffff; font-size: 18px; font-weight: 600; }
        .body { padding: 32px; }
        .label { font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .value { font-size: 15px; color: #111827; margin-bottom: 20px; line-height: 1.5; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-intent { background: #fef3c7; color: #92400e; }
        .badge-no-answer { background: #fee2e2; color: #991b1b; }
        .badge-manual { background: #dbeafe; color: #1e40af; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .chat-bubble { padding: 10px 14px; border-radius: 12px; margin-bottom: 8px; font-size: 14px; line-height: 1.5; max-width: 85%; }
        .bubble-user { background: #111827; color: #ffffff; margin-left: auto; text-align: right; }
        .bubble-assistant { background: #f3f4f6; color: #111827; }
        .bubble-label { font-size: 11px; color: #9ca3af; margin-bottom: 2px; }
        .footer { padding: 20px 32px; background: #f9fafb; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { margin: 0; font-size: 13px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>New Lead Captured</h1>
            </div>
            <div class="body">
                <div class="label">Name</div>
                <div class="value">{{ $lead->name }}</div>

                <div class="label">Contact</div>
                <div class="value">{{ $lead->contact }}</div>

                <div class="label">Trigger</div>
                <div class="value">
                    @if($lead->trigger === 'intent')
                        <span class="badge badge-intent">Buying Intent</span>
                    @elseif($lead->trigger === 'no_answer')
                        <span class="badge badge-no-answer">No Answer Available</span>
                    @else
                        <span class="badge badge-manual">Manual</span>
                    @endif
                </div>

                @if($lead->user_request)
                    <div class="label">Trigger Message</div>
                    <div class="value">{{ $lead->user_request }}</div>
                @endif

                @if($lead->notes)
                    <div class="label">Additional Notes</div>
                    <div class="value">{{ $lead->notes }}</div>
                @endif

                @if($lead->conversation_snapshot && count($lead->conversation_snapshot) > 0)
                    <hr class="divider">
                    <div class="label" style="margin-bottom: 12px;">Conversation Context</div>
                    @foreach($lead->conversation_snapshot as $msg)
                        <div style="display: flex; flex-direction: column; align-items: {{ $msg['role'] === 'user' ? 'flex-end' : 'flex-start' }};">
                            <div class="bubble-label">{{ $msg['role'] === 'user' ? 'Visitor' : 'Bot' }}</div>
                            <div class="chat-bubble {{ $msg['role'] === 'user' ? 'bubble-user' : 'bubble-assistant' }}">
                                {{ Str::limit($msg['content'], 300) }}
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <div class="footer">
                <p>Captured by {{ $lead->client->name }} chatbot &middot; {{ $lead->created_at->format('M j, Y g:i A') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
