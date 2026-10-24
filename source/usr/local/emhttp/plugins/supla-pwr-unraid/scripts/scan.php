<?php
/**
 * Pobiera bieżący odczyt z licznika energii SUPLA (Direct Link) i zwraca
 * znormalizowany JSON. Używane zarówno przez przycisk "Testuj połączenie"
 * w Ustawieniach, jak i do odświeżenia kafelka Dashboard.
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
    require_once __DIR__ . '/../include/SuplaClient.php';

    $cfgFile = '/boot/config/plugins/supla-pwr-unraid/supla-pwr-unraid.cfg';
    if (!file_exists($cfgFile)) {
        throw new Exception('Brak konfiguracji — uzupełnij i zapisz adres Direct Link w Ustawieniach.');
    }

    $cfg = @parse_ini_file($cfgFile);
    if (!is_array($cfg)) {
        throw new Exception('Nie udało się odczytać pliku konfiguracji.');
    }

    $client = new SuplaClient($cfg['DEVICE_URL'] ?? '');
    $data = $client->read();

    // Ręczna cena nadpisuje tę zwróconą przez SUPLA Cloud (przydatne gdy
    // Direct Link jej nie udostępnia albo user ma inną faktyczną taryfę).
    $override = trim((string) ($cfg['PRICE_OVERRIDE'] ?? ''));
    if ($override !== '' && is_numeric($override)) {
        $data['price'] = (float) $override;
    }
    if ($data['price'] !== null && $data['energy'] !== null) {
        $data['cost'] = round($data['price'] * $data['energy'], 2);
    } else {
        $data['cost'] = null;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['ok' => true, 'data' => $data]);
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
