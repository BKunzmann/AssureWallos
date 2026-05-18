<?php

/**
 * AssureWallos Paperless-ngx instance settings (singleton row id = 1).
 */
class Ins_Paperless_Config
{
    private const DEFAULTS = [
        'enabled' => 0,
        'base_url' => '',
        'api_token' => '',
        'match_mode' => 'custom_field',
        'custom_field_name' => 'assure_subscription_id',
        'tag_prefix' => 'aw-sub-',
        'use_policy_number_fallback' => 0,
        'cache_ttl_seconds' => 300,
    ];

    public static function insPaperlessReady(SQLite3 $db): bool
    {
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'assure_paperless_settings'");
        return (bool) $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    public static function insLoad(SQLite3 $db): array
    {
        if (!self::insPaperlessReady($db)) {
            return self::DEFAULTS;
        }

        $result = $db->query('SELECT * FROM assure_paperless_settings WHERE id = 1 LIMIT 1');
        $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;

        if (!$row) {
            return self::DEFAULTS;
        }

        return self::insNormalizeRow($row);
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function insSave(SQLite3 $db, array $input): bool
    {
        if (!self::insPaperlessReady($db)) {
            return false;
        }

        $current = self::insLoad($db);
        $apiToken = trim((string) ($input['api_token'] ?? ''));
        if ($apiToken === '' || $apiToken === '********') {
            $apiToken = (string) $current['api_token'];
        }

        $matchMode = strtolower(trim((string) ($input['match_mode'] ?? 'custom_field')));
        if (!in_array($matchMode, ['custom_field', 'tag', 'search'], true)) {
            $matchMode = 'custom_field';
        }

        $cacheTtl = (int) ($input['cache_ttl_seconds'] ?? 300);
        if ($cacheTtl < 0) {
            $cacheTtl = 0;
        }

        $enabled = self::insTruthy($input['enabled'] ?? 0) ? 1 : 0;
        $useFallback = self::insTruthy($input['use_policy_number_fallback'] ?? 0) ? 1 : 0;

        $stmt = $db->prepare("
            INSERT INTO assure_paperless_settings (
                id, enabled, base_url, api_token, match_mode, custom_field_name,
                tag_prefix, use_policy_number_fallback, cache_ttl_seconds
            ) VALUES (
                1, :enabled, :baseUrl, :apiToken, :matchMode, :customFieldName,
                :tagPrefix, :useFallback, :cacheTtl
            )
            ON CONFLICT(id) DO UPDATE SET
                enabled = excluded.enabled,
                base_url = excluded.base_url,
                api_token = excluded.api_token,
                match_mode = excluded.match_mode,
                custom_field_name = excluded.custom_field_name,
                tag_prefix = excluded.tag_prefix,
                use_policy_number_fallback = excluded.use_policy_number_fallback,
                cache_ttl_seconds = excluded.cache_ttl_seconds
        ");

        $stmt->bindValue(':enabled', $enabled, SQLITE3_INTEGER);
        $stmt->bindValue(':baseUrl', self::insNormalizeBaseUrl((string) ($input['base_url'] ?? '')), SQLITE3_TEXT);
        $stmt->bindValue(':apiToken', $apiToken, SQLITE3_TEXT);
        $stmt->bindValue(':matchMode', $matchMode, SQLITE3_TEXT);
        $stmt->bindValue(':customFieldName', self::insCleanToken((string) ($input['custom_field_name'] ?? 'assure_subscription_id')), SQLITE3_TEXT);
        $stmt->bindValue(':tagPrefix', self::insCleanToken((string) ($input['tag_prefix'] ?? 'aw-sub-')), SQLITE3_TEXT);
        $stmt->bindValue(':useFallback', $useFallback, SQLITE3_INTEGER);
        $stmt->bindValue(':cacheTtl', $cacheTtl, SQLITE3_INTEGER);

        return (bool) $stmt->execute();
    }

    /**
     * Akzeptiert 1, "1", true, "on" (FormData/JSON).
     *
     * @param mixed $value
     */
    private static function insTruthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    }

    public static function insIsConfigured(array $settings): bool
    {
        return !empty($settings['enabled'])
            && $settings['base_url'] !== ''
            && $settings['api_token'] !== '';
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public static function insPublicSettings(array $settings): array
    {
        return [
            'enabled' => (int) ($settings['enabled'] ?? 0),
            'base_url' => (string) ($settings['base_url'] ?? ''),
            'match_mode' => (string) ($settings['match_mode'] ?? 'custom_field'),
            'custom_field_name' => (string) ($settings['custom_field_name'] ?? 'assure_subscription_id'),
            'tag_prefix' => (string) ($settings['tag_prefix'] ?? 'aw-sub-'),
            'use_policy_number_fallback' => (int) ($settings['use_policy_number_fallback'] ?? 0),
            'cache_ttl_seconds' => (int) ($settings['cache_ttl_seconds'] ?? 300),
            'has_token' => ($settings['api_token'] ?? '') !== '',
        ];
    }

    private static function insNormalizeRow(array $row): array
    {
        $settings = self::DEFAULTS;

        foreach (self::DEFAULTS as $key => $default) {
            if (!array_key_exists($key, $row)) {
                continue;
            }
            $settings[$key] = $row[$key];
        }

        $settings['enabled'] = (int) $settings['enabled'];
        $settings['use_policy_number_fallback'] = (int) $settings['use_policy_number_fallback'];
        $settings['cache_ttl_seconds'] = (int) $settings['cache_ttl_seconds'];
        $settings['base_url'] = self::insNormalizeBaseUrl((string) $settings['base_url']);
        $settings['match_mode'] = in_array($settings['match_mode'], ['custom_field', 'tag', 'search'], true)
            ? $settings['match_mode']
            : 'custom_field';

        return $settings;
    }

    private static function insNormalizeBaseUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }

    private static function insCleanToken(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value) ?? '';
    }
}
