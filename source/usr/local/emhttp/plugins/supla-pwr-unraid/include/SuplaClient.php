<?php
/**
 * Klient danych pomiarowych SUPLA Cloud przez "Direct Link" do kanału
 * licznika energii (np. Zamel EFM-01). Direct Link to publiczny URL
 * generowany w aplikacji SUPLA dla konkretnego kanału, bez potrzeby
 * logowania/OAuth — wystarczy sam URL z GET /read?format=json.
 */
declare(strict_types=1);

class SuplaClient
{
    private string $baseUrl;

    public function __construct(string $directLinkUrl)
    {
        $this->baseUrl = rtrim(trim($directLinkUrl), '/');
    }

    /** @return array{power:?float,voltage:?float,current:?float,factor:?float,energy:?float,apparentPower:?float,reactivePower:?float,price:?float,currency:?string} */
    public function read(): array
    {
        if ($this->baseUrl === '') {
            throw new Exception('Nie skonfigurowano adresu Direct Link — uzupełnij go w Ustawieniach.');
        }
        if (!function_exists('curl_init')) {
            throw new Exception('Rozszerzenie PHP cURL jest niedostępne na tym serwerze.');
        }

        $ch = curl_init($this->baseUrl . '/read?format=json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        if ($res === false) {
            throw new Exception('Błąd połączenia z SUPLA Cloud: ' . $err);
        }
        if ($httpCode !== 200) {
            throw new Exception('SUPLA Cloud zwróciło HTTP ' . $httpCode . ' — sprawdź adres Direct Link (musi wskazywać na kanał licznika energii).');
        }

        $data = json_decode($res, true);
        if (!is_array($data)) {
            throw new Exception('Nieprawidłowa odpowiedź JSON z SUPLA Cloud.');
        }
        if (isset($data['error'])) {
            throw new Exception('SUPLA Cloud zwróciło błąd: ' . (is_string($data['error']) ? $data['error'] : json_encode($data['error'])));
        }

        $p = $data['phases'][0] ?? [];

        return [
            'power' => isset($p['powerActive']) ? (float) $p['powerActive'] : null,
            'voltage' => isset($p['voltage']) ? (float) $p['voltage'] : null,
            'current' => isset($p['current']) ? (float) $p['current'] : null,
            'factor' => isset($p['powerFactor']) ? round(((float) $p['powerFactor']) * 100, 1) : null,
            'energy' => isset($p['totalForwardActiveEnergy']) ? (float) $p['totalForwardActiveEnergy'] : null,
            'apparentPower' => isset($p['powerApparent']) ? (float) $p['powerApparent'] : null,
            'reactivePower' => isset($p['powerReactive']) ? (float) $p['powerReactive'] : null,
            'price' => isset($data['pricePerUnit']) ? (float) $data['pricePerUnit'] : null,
            'currency' => $data['currency'] ?? null,
        ];
    }
}
