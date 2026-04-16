<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>New Lead Captured</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* Reset */
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; background-color: #f0f2f5; }

        /* Typography */
        .text-primary { color: #111827; }
        .text-secondary { color: #6b7280; }
        .text-muted { color: #9ca3af; }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .fluid { width: 100% !important; max-width: 100% !important; height: auto !important; }
            .stack-column { display: block !important; width: 100% !important; max-width: 100% !important; }
            .padding-mobile { padding-left: 20px !important; padding-right: 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">

    <!-- Preheader (hidden preview text) -->
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
        New lead from {{ $lead->name }} ({{ $lead->contact }}) via your {{ $lead->client->name }} chatbot.
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <!-- Outer wrapper -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f0f2f5;">
        <tr>
            <td style="padding: 40px 16px;">

                <!-- Email container -->
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="580" style="margin: 0 auto; max-width: 580px;">

                    <!-- Logo / Brand bar -->
                    <tr>
                        <td style="padding: 0 0 24px 0; text-align: center;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="font-size: 20px; font-weight: 700; color: #111827; letter-spacing: -0.3px;">
                                        {{ config('app.name', 'Zao Chat') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main card -->
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);">

                                <!-- Header banner -->
                                <tr>
                                    <td style="background-color: #111827; padding: 28px 36px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td>
                                                    <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 500; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px;">New Lead</p>
                                                    <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; line-height: 1.3;">{{ $lead->name }}</h1>
                                                </td>
                                                <td style="text-align: right; vertical-align: top;">
                                                    @if($lead->trigger === 'intent')
                                                        <span style="display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: rgba(251, 191, 36, 0.2); color: #fbbf24;">Buying Intent</span>
                                                    @elseif($lead->trigger === 'no_answer')
                                                        <span style="display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: rgba(248, 113, 113, 0.2); color: #f87171;">No Answer</span>
                                                    @else
                                                        <span style="display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: rgba(96, 165, 250, 0.2); color: #60a5fa;">Manual</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Contact info section -->
                                <tr>
                                    <td style="padding: 32px 36px 0 36px;" class="padding-mobile">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #f3f4f6;">
                                            <tr>
                                                <td style="padding: 20px 24px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="50%" style="vertical-align: top; padding-right: 12px;">
                                                                <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px;">Contact</p>
                                                                <p style="margin: 0; font-size: 15px; font-weight: 600; color: #111827; line-height: 1.4;">{{ $lead->contact }}</p>
                                                            </td>
                                                            <td width="50%" style="vertical-align: top; padding-left: 12px;">
                                                                <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px;">Captured</p>
                                                                <p style="margin: 0; font-size: 15px; font-weight: 600; color: #111827; line-height: 1.4;">{{ $lead->created_at->format('M j, Y') }}</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Trigger message -->
                                @if($lead->user_request)
                                <tr>
                                    <td style="padding: 28px 36px 0 36px;" class="padding-mobile">
                                        <p style="margin: 0 0 8px 0; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px;">What they asked</p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="border-left: 3px solid #111827; padding: 12px 16px; background-color: #f9fafb; border-radius: 0 8px 8px 0;">
                                                    <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6; font-style: italic;">&ldquo;{{ $lead->user_request }}&rdquo;</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif

                                <!-- Notes -->
                                @if($lead->notes)
                                <tr>
                                    <td style="padding: 28px 36px 0 36px;" class="padding-mobile">
                                        <p style="margin: 0 0 8px 0; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px;">Additional notes</p>
                                        <p style="margin: 0; font-size: 15px; color: #374151; line-height: 1.6;">{{ $lead->notes }}</p>
                                    </td>
                                </tr>
                                @endif

                                <!-- Conversation snapshot -->
                                @if($lead->conversation_snapshot && count($lead->conversation_snapshot) > 0)
                                <tr>
                                    <td style="padding: 28px 36px 0 36px;" class="padding-mobile">
                                        <!-- Divider -->
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr><td style="border-top: 1px solid #e5e7eb; padding-top: 28px;"></td></tr>
                                        </table>
                                        <p style="margin: 0 0 16px 0; font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px;">Conversation</p>

                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #fafafa; border-radius: 8px; border: 1px solid #f3f4f6;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    @foreach($lead->conversation_snapshot as $msg)
                                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: {{ $loop->last ? '0' : '10px' }};">
                                                            <tr>
                                                                @if($msg['role'] === 'user')
                                                                    <td width="15%">&nbsp;</td>
                                                                    <td width="85%">
                                                                        <p style="margin: 0 0 3px 0; font-size: 10px; font-weight: 600; color: #9ca3af; text-align: right; text-transform: uppercase; letter-spacing: 0.5px;">Visitor</p>
                                                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-left: auto;">
                                                                            <tr>
                                                                                <td style="background-color: #111827; color: #ffffff; padding: 10px 14px; border-radius: 12px 12px 4px 12px; font-size: 13px; line-height: 1.5;">
                                                                                    {{ Str::limit($msg['content'], 250) }}
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                @else
                                                                    <td width="85%">
                                                                        <p style="margin: 0 0 3px 0; font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px;">Bot</p>
                                                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                                            <tr>
                                                                                <td style="background-color: #e5e7eb; color: #111827; padding: 10px 14px; border-radius: 12px 12px 12px 4px; font-size: 13px; line-height: 1.5;">
                                                                                    {{ Str::limit($msg['content'], 250) }}
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td width="15%">&nbsp;</td>
                                                                @endif
                                                            </tr>
                                                        </table>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endif

                                <!-- Bottom spacing -->
                                <tr>
                                    <td style="padding: 0 0 32px 0;"></td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 36px; text-align: center;">
                            <p style="margin: 0 0 4px 0; font-size: 13px; color: #9ca3af; line-height: 1.5;">
                                This lead was captured by the <strong style="color: #6b7280;">{{ $lead->client->name }}</strong> chatbot.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #d1d5db;">
                                {{ $lead->created_at->format('M j, Y \a\t g:i A') }} &middot; Powered by {{ config('app.name', 'Zao Chat') }}
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
