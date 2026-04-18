<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clientName }} Assistant</title>
    <link rel="stylesheet" href="{{ $iframeStylesUrl }}">
    <style>
        html,
        body {
            margin: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #ffffff;
        }

        #davey-iframe-root {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <div id="davey-iframe-root"></div>
    <script
        src="{{ $iframeScriptUrl }}"
        data-client-code="{{ $clientCode }}"
        data-api-base="{{ $apiBase }}"
        data-mount-id="davey-iframe-root"
        defer
    ></script>
</body>
</html>
