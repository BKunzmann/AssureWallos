<?php

/**
 * JSON-Antworten für Insurance-Endpunkte: korrekter MIME-Typ, damit Browser und DevTools
 * die Antwort als JSON erkennen (nicht als HTML).
 */
function ins_send_json_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}
