# đź“š EcoBook Hub

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Python: 3.11](https://img.shields.io/badge/Python-3.11-blue.svg)](https://www.python.org/)
[![DuckDB](https://img.shields.io/badge/Engine-DuckDB-FFF000.svg)](https://duckdb.org/)
[![PHP](https://img.shields.io/badge/Frontend-PHP-777BB4.svg)](https://www.php.net/)
[![CI Pipeline](https://github.com/twoj-login/ecobook-hub/actions/workflows/ci.yml/badge.svg)](.github/workflows/ci.yml)

> Otwarta platforma wymiany i sprzedaĹĽy uĹĽywanych podrÄ™cznikĂłw szkolnych zintegrowana z danymi z portalu [dane.gov.pl](https://dane.gov.pl) (Rejestr SzkĂłĹ‚ i PlacĂłwek OĹ›wiatowych â€“ RSPO).

---

## đźŽŻ Problem spoĹ‚eczny i cel projektu

Wysokie koszty nowych podrÄ™cznikĂłw szkolnych stanowiÄ… znaczne obciÄ…ĹĽenie dla domowych budĹĽetĂłw. Oficjalne dane Ministerstwa Edukacji sÄ… publicznie dostÄ™pne, jednak publikowane sÄ… w formie surowych plikĂłw Excel liczÄ…cych tysiÄ…ce rekordĂłw.

Nasz projekt rozwiÄ…zuje ten problem poprzez:
1. **PrzejrzystÄ… wyszukiwarkÄ™ szkĂłĹ‚**: Wygodne filtrowanie placĂłwek wedĹ‚ug **wojewĂłdztwa, powiatu, miasta i typu szkoĹ‚y**.
2. **LokalnÄ… gieĹ‚dÄ™ P2P**: MoĹĽliwoĹ›Ä‡ wystawienia i zakupu uĹĽywanych podrÄ™cznikĂłw bezpoĹ›rednio w obrÄ™bie wĹ‚asnej szkoĹ‚y.
3. **TransparentnoĹ›Ä‡ i analitykÄ™**: Kafelki KPI oraz wykresy pokazujÄ…ce skalÄ™ szkolnictwa w regionach i szacunkowe marnotrawstwo papieru.

---

## đźŹ›ď¸Ź Architektura i optymalizacja wydajnoĹ›ci

Aplikacja zostaĹ‚a zaprojektowana w oparciu o architekturÄ™ in-memory bez koniecznoĹ›ci stawiania zewnÄ™trznych serwerĂłw baz danych:

* **Engine analityczny: [DuckDB](https://duckdb.org)** â€“ ultraszybki, kolumnowy silnik SQL osadzony w procesie aplikacji. Zapytania filtrujÄ…ce i agregujÄ…ce ponad 20 000 placĂłwek wykonujÄ… siÄ™ w uĹ‚amkach milisekund.
* **Format magazynowania: [DuckDB](https://duckdb.org)** â€“ osadzona baza `schools.duckdb` z tabelami `schools` i `listings`, bez zewnÄ™trznego serwera.
* **Interfejs uĹĽytkownika: [Streamlit](https://streamlit.io)** â€“ lekki, responsywny frontend w Pythonie z automatycznym odĹ›wieĹĽaniem widokĂłw.
* **Konteneryzacja: [Docker](https://www.docker.com)** â€“ uruchomienie jednym poleceniem na dowolnym Ĺ›rodowisku.

---

## đźš€ Szybki start (Uruchomienie jednÄ… komendÄ…)

Najprostszym i zalecanym sposobem uruchomienia projektu jest **Docker Compose**:

```bash
docker compose up --build
```

Aplikacja pobierze najĹ›wieĹĽsze dane z `dane.gov.pl`, przetworzy je do bazy DuckDB i uruchomi interfejs pod adresem:
đź‘‰ **[http://localhost:8501](http://localhost:8501)**

Zatrzymanie kontenera:
```bash
docker compose down
```

---

## đź’» Uruchomienie lokalne (bez Dockera)

Wymagany: **Python 3.11+**

1. Sklonuj repozytorium:
   ```bash
   git clone https://github.com/twoj-login/ecobook-hub.git
   cd ecobook-hub
   ```

2. UtwĂłrz i aktywuj Ĺ›rodowisko wirtualne:
   ```bash
   python -m venv .venv
   # Windows:
   .venv\Scripts\activate
   # Linux/macOS:
   source .venv/bin/activate
   ```

3. Zainstaluj zaleĹĽnoĹ›ci produkcyjne:
   ```bash
   pip install -r requirements.txt
   ```

4. Pobierz i zaktualizuj bazÄ™ danych (uĹĽyj Pythona 3.11):
   ```bash
   py -3.11 scripts/download_data.py
   ```

5. Uruchom aplikacjÄ™:
   ```bash
   php -S localhost:8000
   ```
---

## đź§Ş Testy jednostkowe i jakoĹ›Ä‡ kodu

Projekt posiada pokrycie testami jednostkowymi (`pytest`) oraz weryfikacjÄ™ linterem (`ruff`).

1. Zainstaluj zaleĹĽnoĹ›ci deweloperskie:
   ```bash
   pip install -r requirements-dev.txt
   ```

2. Uruchom linter:
   ```bash
   ruff check .
   ```

3. Uruchom testy jednostkowe:
   ```bash
   pytest -v tests/
   ```

*(MoĹĽesz to rĂłwnieĹĽ uruchomiÄ‡ wewnÄ…trz dziaĹ‚ajÄ…cego kontenera: `docker exec ecobook_hub_app pytest -v tests/`)*

---

## đź“ Struktura repozytorium

```text
â”śâ”€â”€ .github/
â”‚   â”śâ”€â”€ workflows/
â”‚   â”‚   â””â”€â”€ ci.yml                 # Automatyczny pipeline GitHub Actions (testy + linter)
â”‚   â”śâ”€â”€ ISSUE_TEMPLATE/            # Szablony zgĹ‚aszania bĹ‚Ä™dĂłw i propozycji funkcji
â”‚   â””â”€â”€ PULL_REQUEST_TEMPLATE.md   # Szablon sprawdzania Pull RequestĂłw
â”śâ”€â”€ docs/
â”‚   â”śâ”€â”€ RAPORT_KONCOWY_ISO21513.md # Raport koĹ„cowy oparty o standard ISO 21513
â”‚   â”śâ”€â”€ LESSONS_LEARNED_PMI.md     # DoĹ›wiadczenia projektowe wedĹ‚ug metodyki PMI
â”‚   â””â”€â”€ KONTROLA_DOKUMENTOW_ISO_10013.md # Rejestr kontroli dokumentĂłw
â”śâ”€â”€ scripts/
â”‚   â””â”€â”€ download_data.py           # Skrypt pobierajÄ…cy dane z API dane.gov.pl
â”śâ”€â”€ src/
â”‚   â”śâ”€â”€ app.py                     # Aplikacja dashboardowa Streamlit + DuckDB
â”‚   â””â”€â”€ db.py                      # Warstwa dostÄ™pu do bazy DuckDB
â”śâ”€â”€ tests/
â”‚   â”śâ”€â”€ conftest.py                # Konfiguracja Ĺ›cieĹĽek testowych
â”‚   â”śâ”€â”€ test_db.py                 # Testy schematu i zapytaĹ„ DuckDB
â”‚   â””â”€â”€ test_download_data.py      # Testy normalizacji danych
â”śâ”€â”€ .env.example                   # PrzykĹ‚adowa konfiguracja zmiennych Ĺ›rodowiskowych
â”śâ”€â”€ .gitignore                     # Blokowanie duĹĽych plikĂłw produkcyjnych i cache'u
â”śâ”€â”€ docker-compose.yml             # Definicja uruchomieniowa kontenera
â”śâ”€â”€ Dockerfile                     # Obraz kontenera Python 3.11-slim
â”śâ”€â”€ pyproject.toml                 # Konfiguracja Ruff i Pytest
â”śâ”€â”€ requirements.txt               # ZaleĹĽnoĹ›ci produkcyjne
â”śâ”€â”€ requirements-dev.txt           # ZaleĹĽnoĹ›ci deweloperskie (pytest, ruff)
â”śâ”€â”€ CHANGELOG.md                   # Historia wersji i zmian w projekcie
â”śâ”€â”€ CODE_OF_CONDUCT.md             # Kodeks postÄ™powania spoĹ‚ecznoĹ›ci
â”śâ”€â”€ CONTRIBUTING.md                # Przewodnik kontrybucji do projektu
â”śâ”€â”€ LICENSE                        # Licencja MIT
â””â”€â”€ README.md                      # Dokumentacja gĹ‚Ăłwna projektu
```

---

## đź“Š ĹąrĂłdĹ‚o danych

Dane pochodzÄ… z oficjalnego rejestru publicznego udostÄ™pnianego przez Ministerstwo Edukacji:
* **ZbiĂłr danych:** *Rejestr SzkĂłĹ‚ i PlacĂłwek OĹ›wiatowych (RSPO)*
* **Portal:** [dane.gov.pl (zasĂłb ID 1254769)](https://dane.gov.pl)
* **Aktualizacja:** Skrypt `scripts/download_data.py` automatycznie odpytuje endpoint API `https://api.dane.gov.pl/1.4/resources/1254769` i generuje zoptymalizowanÄ… bazÄ™ DuckDB.

---

## đź“„ Dokumentacja formalna

SzczegĂłĹ‚owa dokumentacja zarzÄ…dcza znajduje siÄ™ w katalogu `docs/`:
* đź“ [Raport koĹ„cowy wg ISO 21513](docs/RAPORT_KONCOWY_ISO21513.md) â€“ cele, rezultaty, plan vs realizacja, wpĹ‚yw spoĹ‚eczny i wnioski (zgodnie z kontrolÄ… dokumentĂłw ISO 10013).
* đź“— [Lessons Learned wg PMI](docs/LESSONS_LEARNED_PMI.md) â€“ rejestr doĹ›wiadczeĹ„ projektowych, sukcesy, wyzwania i rekomendacje (zgodnie z kontrolÄ… dokumentĂłw ISO 10013).

---

## đź¤ť WspĂłĹ‚praca (Contributing)

Zapraszamy do wspĂłĹ‚tworzenia projektu! Zapoznaj siÄ™ z [CONTRIBUTING.md](CONTRIBUTING.md) oraz [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## đź‘¤ Autor

* **ZespĂłĹ‚ EcoBook Hub** â€“ pomysĹ‚, implementacja, testy i dokumentacja

## âš–ď¸Ź Licencja

Projekt jest udostÄ™pniony na warunkach otwartej licencji [MIT](LICENSE). (c) 2026 EcoBook Hub.

