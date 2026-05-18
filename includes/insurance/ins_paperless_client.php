<?php

require_once __DIR__ . '/../ssrf_helper.php';

/**
 * HTTP client for Paperless-ngx REST API (read-only).
 */
class Ins_Paperless_Client
{
    private const TIMEOUT_SECONDS = 20;
    private const CONNECT_TIMEOUT_SECONDS = 8;

    /**
     * @param array<string, scalar> $queryParams
     * @return array{success: bool, status: int, body: ?array, error: ?string}
     */
    public static function insGet(
        SQLite3 $db,
        int $userId,
        string $baseUrl,
        string $apiToken,
        string $path,
        array $queryParams = []
    ): array {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '' || $apiToken === '') {
            return self::insError('Paperless ist nicht konfiguriert.');
        }

        $path = '/' . ltrim($path, '/');
        $query = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        $url = $baseUrl . $path . ($query !== '' ? '?' . $query : '');

        $ssrf = is_url_safe_for_ssrf($url, $db, $userId);
        if ($ssrf === false) {
            error_log('AssureWallos Paperless: SSRF check failed for ' . $url);
            return self::insError('Paperless-URL ist nicht erlaubt (SSRF-Schutz). Admin: Webhook-Allowlist prüfen.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . $apiToken,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RESOLVE, ["{$ssrf['host']}:{$ssrf['port']}:{$ssrf['ip']}"]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        unset($ch);

        if ($raw === false) {
            error_log('AssureWallos Paperless cURL: ' . $curlError);
            return self::insError('Paperless ist nicht erreichbar.');
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            error_log('AssureWallos Paperless: invalid JSON (HTTP ' . $status . ')');
            return [
                'success' => false,
                'status' => $status,
                'body' => null,
                'error' => $status >= 400 ? 'Paperless hat einen Fehler gemeldet.' : 'Ungültige Antwort von Paperless.',
            ];
        }

        if ($status < 200 || $status >= 300) {
            error_log('AssureWallos Paperless HTTP ' . $status . ': ' . substr((string) $raw, 0, 500));
            return [
                'success' => false,
                'status' => $status,
                'body' => $decoded,
                'error' => 'Paperless hat einen Fehler gemeldet (HTTP ' . $status . ').',
            ];
        }

        return [
            'success' => true,
            'status' => $status,
            'body' => $decoded,
            'error' => null,
        ];
    }

    /**
     * Binary GET (e.g. document thumbnail). Token stays server-side.
     *
     * @return array{success: bool, status: int, body: ?string, content_type: ?string, error: ?string}
     */
    public static function insGetBinary(
        SQLite3 $db,
        int $userId,
        string $baseUrl,
        string $apiToken,
        string $path
    ): array {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '' || $apiToken === '') {
            return self::insBinaryError('Paperless ist nicht konfiguriert.');
        }

        $path = '/' . ltrim($path, '/');
        $url = $baseUrl . $path;

        $ssrf = is_url_safe_for_ssrf($url, $db, $userId);
        if ($ssrf === false) {
            error_log('AssureWallos Paperless: SSRF check failed for ' . $url);
            return self::insBinaryError('Paperless-URL ist nicht erlaubt (SSRF-Schutz).');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . $apiToken,
            'Accept: image/*,*/*',
        ]);
        curl_setopt($ch, CURLOPT_RESOLVE, ["{$ssrf['host']}:{$ssrf['port']}:{$ssrf['ip']}"]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        unset($ch);

        if ($raw === false) {
            error_log('AssureWallos Paperless cURL (binary): ' . $curlError);
            return self::insBinaryError('Paperless ist nicht erreichbar.');
        }

        if ($status < 200 || $status >= 300) {
            error_log('AssureWallos Paperless thumb HTTP ' . $status);
            return [
                'success' => false,
                'status' => $status,
                'body' => null,
                'content_type' => null,
                'error' => 'Vorschau konnte nicht geladen werden.',
            ];
        }

        $type = is_string($contentType) && $contentType !== '' ? strtok($contentType, ';') : 'image/png';

        return [
            'success' => true,
            'status' => $status,
            'body' => (string) $raw,
            'content_type' => $type ?: 'image/png',
            'error' => null,
        ];
    }

    /**
     * @param array<string, scalar> $queryParams
     * @return array{success: bool, documents: array<int, array<string, mixed>>, error: ?string}
     */
    public static function insListDocuments(
        SQLite3 $db,
        int $userId,
        string $baseUrl,
        string $apiToken,
        array $queryParams = []
    ): array {
        $queryParams['page_size'] = $queryParams['page_size'] ?? 50;
        $allResults = [];
        $page = 1;

        do {
            $queryParams['page'] = $page;
            $response = self::insGet($db, $userId, $baseUrl, $apiToken, '/api/documents/', $queryParams);

            if (!$response['success'] || !is_array($response['body'])) {
                return [
                    'success' => false,
                    'documents' => [],
                    'error' => $response['error'] ?? 'Paperless ist nicht erreichbar.',
                ];
            }

            $batch = $response['body']['results'] ?? [];
            if (!is_array($batch)) {
                break;
            }

            foreach ($batch as $item) {
                if (is_array($item)) {
                    $allResults[] = $item;
                }
            }

            $next = $response['body']['next'] ?? null;
            $page++;
        } while ($next && $page <= 10);

        return [
            'success' => true,
            'documents' => $allResults,
            'error' => null,
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function insTestConnection(SQLite3 $db, int $userId, string $baseUrl, string $apiToken): array
    {
        $response = self::insGet($db, $userId, $baseUrl, $apiToken, '/api/documents/', ['page_size' => 1]);

        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Verbindung zu Paperless erfolgreich.',
            ];
        }

        return [
            'success' => false,
            'message' => $response['error'] ?? 'Verbindung fehlgeschlagen.',
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    public static function insNormalizeDocument(array $raw, string $baseUrl): array
    {
        $id = (int) ($raw['id'] ?? 0);
        $title = trim((string) ($raw['title'] ?? ''));
        if ($title === '') {
            $title = 'Dokument #' . $id;
        }

        $created = (string) ($raw['created'] ?? $raw['created_date'] ?? $raw['added'] ?? '');

        return [
            'id' => $id,
            'title' => $title,
            'created' => $created,
            'document_type' => $raw['document_type'] ?? null,
            'correspondent' => $raw['correspondent'] ?? null,
            'tags' => is_array($raw['tags'] ?? null) ? $raw['tags'] : [],
            'archive_url' => rtrim($baseUrl, '/') . '/documents/' . $id . '/',
        ];
    }

    /**
     * @return array{success: bool, status: int, body: ?array, error: ?string}
     */
    private static function insError(string $message): array
    {
        return [
            'success' => false,
            'status' => 0,
            'body' => null,
            'error' => $message,
        ];
    }

    /**
     * @return array{success: bool, status: int, body: ?string, content_type: ?string, error: ?string}
     */
    private static function insBinaryError(string $message): array
    {
        return [
            'success' => false,
            'status' => 0,
            'body' => null,
            'content_type' => null,
            'error' => $message,
        ];
    }
}
