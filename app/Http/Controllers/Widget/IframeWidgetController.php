<?php

namespace App\Http\Controllers\Widget;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IframeWidgetController extends Controller
{
    public function show(Request $request, string $clientCode): Response
    {
        $client = Client::query()
            ->where('unique_code', $clientCode)
            ->where('status', 'active')
            ->firstOrFail();

        $scriptPath = public_path('iframe-widget/iframe-widget.js');
        $stylePath = public_path('iframe-widget/iframe-widget.css');
        $scriptVersion = file_exists($scriptPath) ? filemtime($scriptPath) : time();
        $styleVersion = file_exists($stylePath) ? filemtime($stylePath) : time();
        $previewIframeStyle = $request->query('preview_iframe_style');
        $iframeStyleOverride = in_array($previewIframeStyle, Client::WIDGET_STYLES, true)
            ? $previewIframeStyle
            : null;
        $previewIframeTheme = $request->query('preview_iframe_theme');
        $iframeThemeOverride = in_array($previewIframeTheme, Client::WIDGET_THEME_MODES, true)
            ? $previewIframeTheme
            : null;

        $response = response()->view('widget.iframe', [
            'clientCode' => $client->unique_code,
            'clientName' => $client->name,
            'apiBase' => url('/'),
            'iframeScriptUrl' => url('/iframe-widget/iframe-widget.js').'?v='.$scriptVersion,
            'iframeStylesUrl' => url('/iframe-widget/iframe-widget.css').'?v='.$styleVersion,
            'iframeStyleOverride' => $iframeStyleOverride,
            'iframeThemeOverride' => $iframeThemeOverride,
        ]);

        // Iframe embed must be allowed on approved client domains.
        // Use CSP frame-ancestors and avoid X-Frame-Options on this route.
        $response->headers->set(
            'Content-Security-Policy',
            'frame-ancestors '.$this->buildFrameAncestorsDirective($client)
        );
        $response->headers->remove('X-Frame-Options');

        return $response;
    }

    private function buildFrameAncestorsDirective(Client $client): string
    {
        $domains = collect($client->allowed_domains ?? [])
            ->filter(fn ($domain): bool => is_string($domain) && trim($domain) !== '')
            ->map(fn (string $domain): string => strtolower(trim($domain)))
            ->unique()
            ->values();

        if ($domains->isEmpty()) {
            return '*';
        }

        $sources = collect(["'self'"]);

        foreach ($domains as $domain) {
            $normalizedDomain = ltrim($domain, '.');
            if ($normalizedDomain === '') {
                continue;
            }

            if (str_starts_with($normalizedDomain, '*.')) {
                $normalizedDomain = substr($normalizedDomain, 2);
            }

            if ($normalizedDomain === '') {
                continue;
            }

            if (in_array($normalizedDomain, ['localhost', '127.0.0.1'], true)) {
                $sources->push("http://{$normalizedDomain}:*");
                $sources->push("https://{$normalizedDomain}:*");
                continue;
            }

            $sources->push("https://{$normalizedDomain}");
            $sources->push("http://{$normalizedDomain}");
            $sources->push("https://*.{$normalizedDomain}");
            $sources->push("http://*.{$normalizedDomain}");
        }

        return $sources
            ->unique()
            ->values()
            ->implode(' ');
    }
}
