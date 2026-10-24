<?php
/**
 * Zapisuje ustawienia pluginu supla-pwr-unraid (Direct Link URL, interwał
 * odświeżania, opcjonalne nadpisanie ceny). Wywoływane przez fetch() z
 * poziomu frontendu (JSON in, JSON out).
 *
 * Odporne na błędy PHP: niezależnie od tego co się wysypie, zawsze zwraca
 * poprawny JSON zamiast pustej/HTML odpowiedzi (przyczyna błędu
 * "Unexpected end of JSON input" po stronie przeglądarki).
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Błąd serwera: ' . $err['message']]);
    }
});

try {
    $cfgDir = '/boot/config/plugins/supla-pwr-unraid';
    $cfgFile = $cfgDir . '/supla-pwr-unraid.cfg';

    if (!is_dir($cfgDir) && !mkdir($cfgDir, 0755, true) && !is_dir($cfgDir)) {
        throw new Exception('Nie udało się utworzyć katalogu konfiguracji: ' . $cfgDir);
    }

    $raw = file_get_contents('php://input');
    $in = json_decode($raw, true);
    if (!is_array($in)) {
        $in = $_POST;
    }

    $cfgEscape = function ($v) {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $v);
    };

    $data = [
        'DEVICE_URL' => $in['device_url'] ?? '',
        'UIREFRESH' => $in['uirefresh'] ?? '10',
        'PRICE_OVERRIDE' => $in['price_override'] ?? '',
    ];

    $lines = [];
    foreach ($data as $k => $v) {
        $lines[] = $k . '="' . $cfgEscape($v) . '"';
    }
    if (file_put_contents($cfgFile, implode("\n", $lines) . "\n") === false) {
        throw new Exception('Nie udało się zapisać pliku konfiguracji: ' . $cfgFile);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
