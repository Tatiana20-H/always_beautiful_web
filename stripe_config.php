<?php

function stripeConfigValue(string $name, string $default = ''): string
{
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return trim((string) $value);
    }

    static $localConfig = null;
    if ($localConfig === null) {
        $path = __DIR__ . '/.env';
        $localConfig = is_readable($path) ? parse_ini_file($path, false, INI_SCANNER_RAW) : [];
        if (!is_array($localConfig)) {
            $localConfig = [];
        }
    }

    return trim((string) ($localConfig[$name] ?? $default));
}

function stripeSecretKey(): string
{
    return stripeConfigValue('STRIPE_SECRET_KEY');
}

if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', stripeSecretKey());
}
if (!defined('STRIPE_WEBHOOK_SECRET')) {
    define('STRIPE_WEBHOOK_SECRET', stripeConfigValue('STRIPE_WEBHOOK_SECRET'));
}

function stripeModoPruebaDisponible(): bool
{
    return (bool) preg_match('/^(sk_test_|rk_test_)[A-Za-z0-9]+$/', STRIPE_SECRET_KEY)
        && function_exists('curl_init');
}

function stripeBaseUrl(): string
{
    $baseUrl = rtrim(stripeConfigValue('APP_BASE_URL'), '/');
    if ($baseUrl !== '') {
        $configured = parse_url($baseUrl);
        $requestHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (is_array($configured) && in_array(strtolower((string) ($configured['host'] ?? '')), ['localhost', '127.0.0.1'], true) &&
            preg_match('/^(localhost|127\.0\.0\.1):\d+$/i', $requestHost)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                ? 'https'
                : strtolower((string) ($configured['scheme'] ?? 'http'));
            return $scheme . '://' . $requestHost;
        }
        return $baseUrl;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return '';
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https://' : 'http://') . $host;
}

function stripeRequest(string $method, string $path, array $parameters = []): array
{
    $secretKey = stripeSecretKey();
    if ($secretKey === '' || !function_exists('curl_init')) {
        return ['success' => false, 'error' => 'Stripe o cURL no están configurados.'];
    }

    $curl = curl_init('https://api.stripe.com' . $path);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_USERPWD => $secretKey . ':',
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ];

    if ($parameters !== []) {
        $options[CURLOPT_POSTFIELDS] = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
    }

    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    $data = is_string($response) ? json_decode($response, true) : null;
    if ($curlError !== '' || $status < 200 || $status >= 300 || !is_array($data)) {
        return [
            'success' => false,
            'error' => $data['error']['message'] ?? ($curlError ?: 'Stripe devolvió una respuesta inválida.'),
            'status' => $status,
        ];
    }

    return ['success' => true, 'data' => $data, 'status' => $status];
}