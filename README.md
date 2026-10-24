# Supla Power Monitor — plugin do unRAID

Plugin do unRAID OS wyświetlający w WebGUI bieżący odczyt licznika energii
podłączonego do ekosystemu SUPLA (np. Zamel EFM-01) — moc, napięcie, prąd,
zużycie i koszt, pobierane przez publiczny **Direct Link** SUPLA Cloud.

Zbudowany od zera jako nowy, niezależny projekt — inspirowany ideą
[suplapm-unraid](https://github.com/MatisOne/suplapm-unraid), ale z architekturą
zgodną z konwencją pluginów KlimasTech (patrz
[unifi-site-scan](https://github.com/KlimasTech/unifi-site-scan)): kafelek
Dashboard montowany w natywnej tabeli `#db_box1`, strony WebGUI w PascalCase,
AJAX endpointy odporne na błędy PHP (zawsze zwracają poprawny JSON), test
połączenia na żywo.

## Funkcje

- **Dashboard** — kafelek z bieżącą mocą w nagłówku + siatka: napięcie,
  prąd, moc, zużycie (kWh), koszt, współczynnik mocy (cos φ)
- **Ustawienia** — adres Direct Link, interwał odświeżania UI, opcjonalne
  ręczne nadpisanie ceny energii
- **Testuj połączenie** — natychmiastowy podgląd żywych danych bez
  czekania na odświeżenie Dashboardu
- Wbudowana instrukcja tworzenia Direct Linka w SUPLA Cloud
- Autoodświeżanie, spójny ciemny motyw

## Dlaczego Direct Link, nie API z logowaniem

SUPLA Cloud udostępnia dla pojedynczego kanału publiczny, read-only URL
("Direct Link") zwracający JSON pod `/read?format=json` — bez potrzeby
przechowywania loginu/hasła/tokenu OAuth na serwerze unRAID. Wystarczy sam
URL, ograniczony tylko do jednego kanału (licznika energii).

## Instalacja

**Przez WebGUI unRAID:** Plugins → Install Plugin → wklej:
```
https://raw.githubusercontent.com/KlimasTech/supla-pwr-unraid/main/supla-pwr-unraid.plg
```

**Z terminala unRAID:**
```bash
plugin install https://raw.githubusercontent.com/KlimasTech/supla-pwr-unraid/main/supla-pwr-unraid.plg
```

Po instalacji: **Settings → Utilities → Supla Power Monitor**.

## Konfiguracja

1. SUPLA Cloud → kanał licznika energii → **Bezpośrednie linki** → utwórz
   nowy z uprawnieniem odczytu stanu → skopiuj URL.
2. Zakładka **⚙️ Ustawienia** → wklej URL, ustaw interwał odświeżania →
   **Zapisz**.
3. **⚡ Testuj połączenie** — sprawdza żywy odczyt bez czekania na Dashboard.
4. Wróć do **📡 Dashboard** — kafelek odświeża się automatycznie.

## Struktura projektu

```
source/usr/local/emhttp/plugins/supla-pwr-unraid/
├── SuplaPwrUnraid.page              # strona WebGUI Ustawień (PHP+HTML+JS+CSS)
├── supla-pwr-unraid-dashboard.page  # kafelek Dashboard (montowany w #db_box1)
├── default.cfg                       # domyślna konfiguracja
├── include/
│   └── SuplaClient.php               # klient Direct Link (curl + normalizacja odczytu)
└── scripts/
    ├── save.php                       # AJAX: zapis ustawień do cfg
    └── scan.php                       # AJAX: odczyt na żywo, zwraca JSON
supla-pwr-unraid.plg                  # manifest instalacyjny
pkg_build.sh                           # budowa paczki .txz (makepkg, uruchamiać na unRAID)
```

## Budowanie ze źródeł

```bash
# na serwerze unRAID (ma makepkg)
git clone https://github.com/KlimasTech/supla-pwr-unraid.git
cd supla-pwr-unraid
./pkg_build.sh 2026.10.24
# wypisze ścieżkę .txz i sumę MD5 — wklej MD5 do supla-pwr-unraid.plg
```

## Licencja

MIT
