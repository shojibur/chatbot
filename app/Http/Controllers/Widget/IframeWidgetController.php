<?php

namespace App\Http\Controllers\Widget;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IframeWidgetController extends Controller
{
    public function show(Request $request, string $clientCode): View
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

        return view('widget.iframe', [
            'clientCode' => $client->unique_code,
            'clientName' => $client->name,
            'apiBase' => url('/'),
            'iframeScriptUrl' => url('/iframe-widget/iframe-widget.js').'?v='.$scriptVersion,
            'iframeStylesUrl' => url('/iframe-widget/iframe-widget.css').'?v='.$styleVersion,
            'iframeStyleOverride' => $iframeStyleOverride,
            'iframeThemeOverride' => $iframeThemeOverride,
        ]);
    }
}
