<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('routebook.ui.title', 'API Documentation') }}</title>
    <link rel="stylesheet" href="{{ config('routebook.ui.swagger_ui_css') }}">
    <style>
        body {
            margin: 0;
            background: #f6f8fa;
            color: #1f2937;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .swagger-ui .topbar {
            display: none;
        }

        .routebook-toolbar {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #d8dee4;
            display: flex;
            gap: 12px;
            min-height: 58px;
            padding: 10px 16px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .routebook-toolbar label {
            display: grid;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .routebook-toolbar select,
        .routebook-toolbar input {
            border: 1px solid #c9d1d9;
            border-radius: 6px;
            font-size: 14px;
            min-height: 36px;
            padding: 6px 10px;
        }

        .routebook-toolbar select {
            min-width: 220px;
        }

        .routebook-actions {
            align-items: end;
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .routebook-actions a {
            align-items: center;
            background: #f6f8fa;
            border: 1px solid #c9d1d9;
            border-radius: 6px;
            color: #24292f;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            min-height: 36px;
            padding: 0 12px;
            text-decoration: none;
        }

        @media (max-width: 720px) {
            .routebook-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .routebook-toolbar select,
            .routebook-toolbar input {
                width: 100%;
            }

            .routebook-actions {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="routebook-toolbar">
    <label>
        Definition
        <select id="routebook-definition">
            <option value="">All endpoints</option>
        </select>
    </label>
    <div class="routebook-actions">
        <a id="routebook-export-spec" href="#">Spec</a>
        <a id="routebook-export-postman" href="#">Postman</a>
    </div>
</div>
<div id="swagger-ui"></div>
<script src="{{ config('routebook.ui.swagger_ui_js') }}"></script>
@php
    $specUrl = url(trim(config('routebook.routes.prefix', 'docs'), '/') . '/' . trim(config('routebook.routes.json', 'spec.json'), '/'));
    $docsUrl = url(trim(config('routebook.routes.prefix', 'docs'), '/'));
    $configuredToken = config('routebook.auth.token');
    $authScheme = config('routebook.auth.scheme', 'bearerAuth');
@endphp
<script>
    window.onload = async function () {
        const specUrl = @json($specUrl);
        const docsUrl = @json($docsUrl);
        const authScheme = @json($authScheme);
        const configuredToken = @json($configuredToken);
        const definitionSelect = document.getElementById('routebook-definition');
        const exportSpec = document.getElementById('routebook-export-spec');
        const exportPostman = document.getElementById('routebook-export-postman');
        let currentToken = configuredToken || '';
        let originalSpec = null;

        const exportUrl = function (path) {
            const selected = definitionSelect.value;
            const query = selected ? '?group=' + encodeURIComponent(selected) : '';

            return path + query;
        };

        const updateExportLinks = function () {
            exportSpec.href = exportUrl(specUrl);
            exportPostman.href = exportUrl(docsUrl + '/export/postman');
        };

        const applyToken = function (token) {
            currentToken = token || '';

            if (window.ui && currentToken !== '') {
                window.ui.authActions.authorize({
                    [authScheme]: {
                        name: authScheme,
                        schema: {
                            type: 'http',
                            scheme: 'bearer'
                        },
                        value: currentToken
                    }
                });
            }
        };

        const filterSpecByTag = function (spec, tag) {
            if (!tag) {
                return spec;
            }

            const filtered = JSON.parse(JSON.stringify(spec));
            filtered.paths = {};

            Object.entries(spec.paths || {}).forEach(([path, operations]) => {
                Object.entries(operations).forEach(([method, operation]) => {
                    if ((operation.tags || []).includes(tag)) {
                        filtered.paths[path] = filtered.paths[path] || {};
                        filtered.paths[path][method] = operation;
                    }
                });
            });

            return filtered;
        };

        const fillDefinitionSelect = function (spec) {
            const tags = new Set();

            Object.values(spec.paths || {}).forEach((operations) => {
                Object.values(operations).forEach((operation) => {
                    (operation.tags || []).forEach((tag) => tags.add(tag));
                });
            });

            [...tags].sort().forEach((tag) => {
                const option = document.createElement('option');
                option.value = tag;
                option.textContent = tag;
                definitionSelect.appendChild(option);
            });
        };

        originalSpec = await fetch(specUrl).then((response) => response.json());
        fillDefinitionSelect(originalSpec);
        updateExportLinks();

        window.ui = SwaggerUIBundle({
            spec: originalSpec,
            dom_id: '#swagger-ui',
            deepLinking: true,
            persistAuthorization: true,
            requestInterceptor: function (request) {
                if (currentToken !== '') {
                    request.headers = request.headers || {};
                    request.headers.Authorization = 'Bearer ' + currentToken.replace(/^Bearer\s+/i, '');
                }

                return request;
            },
            presets: [
                SwaggerUIBundle.presets.apis
            ],
            layout: 'BaseLayout'
        });

        definitionSelect.addEventListener('change', function () {
            window.ui.specActions.updateJsonSpec(filterSpecByTag(originalSpec, definitionSelect.value));
            updateExportLinks();
        });

        if (currentToken !== '') {
            applyToken(currentToken);
        }
    };
</script>
</body>
</html>
