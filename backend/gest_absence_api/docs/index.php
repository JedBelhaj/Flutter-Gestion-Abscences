<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gest Absence API Docs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: #f5f7fb;
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .topbar {
            background: #0f172a;
            color: #e2e8f0;
            padding: 10px 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .topbar a {
            color: #93c5fd;
            text-decoration: none;
        }

        .topbar a:hover {
            text-decoration: underline;
        }

        #swagger-ui {
            height: calc(100% - 48px);
        }
    </style>
</head>
<body>
<div class="topbar">
    <div>Gest Absence API Interactive Docs</div>
    <div>
        Spec: <a href="./openapi.json" target="_blank" rel="noopener">openapi.json</a>
    </div>
</div>
<div id="swagger-ui"></div>

<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
<script>
    window.ui = SwaggerUIBundle({
        url: './openapi.json',
        dom_id: '#swagger-ui',
        deepLinking: true,
        displayRequestDuration: true,
        filter: true,
        persistAuthorization: true,
        tryItOutEnabled: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        layout: 'BaseLayout'
    });
</script>
</body>
</html>
