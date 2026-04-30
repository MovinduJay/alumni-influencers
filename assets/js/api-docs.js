(function () {
    'use strict';

    var root = document.getElementById('swagger-ui');
    if (!root || typeof SwaggerUIBundle === 'undefined' || typeof SwaggerUIStandalonePreset === 'undefined') {
        return;
    }

    SwaggerUIBundle({
        url: root.getAttribute('data-spec-url'),
        dom_id: '#swagger-ui',
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        layout: 'StandaloneLayout',
        deepLinking: true,
        validatorUrl: null
    });
}());
