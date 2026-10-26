# 📚 EcoBook Hub

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Python: 3.11](https://img.shields.io/badge/Python-3.11-blue.svg)](https://www.python.org/)
[![DuckDB](https://img.shields.io/badge/Engine-DuckDB-FFF000.svg)](https://duckdb.org/)
[![Streamlit](https://img.shields.io/badge/Frontend-Streamlit-FF4B4B.svg)](https://streamlit.io/)
[![CI Pipeline](https://github.com/twoj-login/ecobook-hub/actions/workflows/ci.yml/badge.svg)](.github/workflows/ci.yml)

> Otwarta platforma wymiany i sprzedaży używanych podręczników szkolnych zintegrowana z danymi z portalu [dane.gov.pl](https://dane.gov.pl) (Rejestr Szkół i Placówek Oświatowych – RSPO).

---

## 🎯 Problem społeczny i cel projektu

Wysokie koszty nowych podręczników szkolnych stanowią znaczne obciążenie dla domowych budżetów. Oficjalne dane Ministerstwa Edukacji są publicznie dostępne, jednak publikowane są w formie surowych plików Excel liczących tysiące rekordów.

Nasz projekt rozwiązuje ten problem poprzez:
1. **Przejrzystą wyszukiwarkę szkół**: Wygodne filtrowanie placówek według **województwa, powiatu, miasta i typu szkoły**.
2. **Lokalną giełdę P2P**: Możliwość wystawienia i zakupu używanych podręczników bezpośrednio w obrębie własnej szkoły.
3. **Transparentność i analitykę**: Kafelki KPI oraz wykresy pokazujące skalę szkolnictwa w regionach i szacunkowe marnotrawstwo papieru.

---

## 🏛️ Architektura i optymalizacja wydajności

Aplikacja została zaprojektowana w oparciu o architekturę in-memory bez konieczności stawiania zewnętrznych serwerów baz danych:

* **Engine analityczny: [DuckDB](https://duckdb.org)** – ultraszybki, kolumnowy silnik SQL osadzony w procesie aplikacji. Zapytania filtrujące i agregujące ponad 20 000 placówek wykonują się w ułamkach milisekund.
* **Format magazynowania: [DuckDB](https://duckdb.org)** – osadzona baza `schools.duckdb` z tabelami `schools` i `listings`, bez zewnętrznego serwera.
* **Interfejs użytkownika: [Streamlit](https://streamlit.io)** – lekki, responsywny frontend w Pythonie z automatycznym odświeżaniem widoków.
* **Konteneryzacja: [Docker](https://www.docker.com)** – uruchomienie jednym poleceniem na dowolnym środowisku.

---

## 🚀 Szybki start (Uruchomienie jedną komendą)

Najprostszym i zalecanym sposobem uruchomienia projektu jest **Docker Compose**:

```bash
docker compose up --build
```

Aplikacja pobierze najświeższe dane z `dane.gov.pl`, przetworzy je do bazy DuckDB i uruchomi interfejs pod adresem:
👉 **[http://localhost:8501](http://localhost:8501)**

Zatrzymanie kontenera:
```bash
docker compose down
```

---

## 💻 Uruchomienie lokalne (bez Dockera)

Wymagany: **Python 3.11+**

1. Sklonuj repozytorium:
   ```bash
   git clone https://github.com/twoj-login/ecobook-hub.git
   cd ecobook-hub
   ```

2. Utwórz i aktywuj środowisko wirtualne:
   ```bash
   python -m venv .venv
   # Windows:
   .venv\Scripts\activate
   # Linux/macOS:
   source .venv/bin/activate
   ```

3. Zainstaluj zależności produkcyjne:
   ```bash
   pip install -r requirements.txt
   ```

4. Pobierz i zaktualizuj bazę danych:
   ```bash
   python scripts/download_data.py
   ```

5. Uruchom aplikację:
   ```bash
   streamlit run src/app.py --server.port 8501
   ```
---

## 🧪 Testy jednostkowe i jakość kodu

Projekt posiada pokrycie testami jednostkowymi (`pytest`) oraz weryfikację linterem (`ruff`).

1. Zainstaluj zależności deweloperskie:
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

*(Możesz to również uruchomić wewnątrz działającego kontenera: `docker exec ecobook_hub_app pytest -v tests/`)*

---

## 📁 Struktura repozytorium

```text
├── .github/
│   ├── workflows/
│   │   └── ci.yml                 # Automatyczny pipeline GitHub Actions (testy + linter)
│   ├── ISSUE_TEMPLATE/            # Szablony zgłaszania błędów i propozycji funkcji
│   └── PULL_REQUEST_TEMPLATE.md   # Szablon sprawdzania Pull Requestów
├── docs/
│   ├── RAPORT_KONCOWY_ISO21513.md # Raport końcowy oparty o standard ISO 21513
│   ├── LESSONS_LEARNED_PMI.md     # Doświadczenia projektowe według metodyki PMI
│   └── KONTROLA_DOKUMENTOW_ISO_10013.md # Rejestr kontroli dokumentów
├── scripts/
│   └── download_data.py           # Skrypt pobierający dane z API dane.gov.pl
├── src/
│   ├── app.py                     # Aplikacja dashboardowa Streamlit + DuckDB
│   └── db.py                      # Warstwa dostępu do bazy DuckDB
├── tests/
│   ├── conftest.py                # Konfiguracja ścieżek testowych
│   ├── test_db.py                 # Testy schematu i zapytań DuckDB
│   └── test_download_data.py      # Testy normalizacji danych
├── .env.example                   # Przykładowa konfiguracja zmiennych środowiskowych
├── .gitignore                     # Blokowanie dużych plików produkcyjnych i cache'u
├── docker-compose.yml             # Definicja uruchomieniowa kontenera
├── Dockerfile                     # Obraz kontenera Python 3.11-slim
├── pyproject.toml                 # Konfiguracja Ruff i Pytest
├── requirements.txt               # Zależności produkcyjne
├── requirements-dev.txt           # Zależności deweloperskie (pytest, ruff)
├── CHANGELOG.md                   # Historia wersji i zmian w projekcie
├── CODE_OF_CONDUCT.md             # Kodeks postępowania społeczności
├── CONTRIBUTING.md                # Przewodnik kontrybucji do projektu
├── LICENSE                        # Licencja MIT
└── README.md                      # Dokumentacja główna projektu
```

---

## 📊 Źródło danych

Dane pochodzą z oficjalnego rejestru publicznego udostępnianego przez Ministerstwo Edukacji:
* **Zbiór danych:** *Rejestr Szkół i Placówek Oświatowych (RSPO)*
* **Portal:** [dane.gov.pl (zasób ID 1254769)](https://dane.gov.pl)
* **Aktualizacja:** Skrypt `scripts/download_data.py` automatycznie odpytuje endpoint API `https://api.dane.gov.pl/1.4/resources/1254769` i generuje zoptymalizowaną bazę DuckDB.

---

## 📄 Dokumentacja formalna

Szczegółowa dokumentacja zarządcza znajduje się w katalogu `docs/`:
* 📘 [Raport końcowy wg ISO 21513](docs/RAPORT_KONCOWY_ISO21513.md) – cele, rezultaty, plan vs realizacja, wpływ społeczny i wnioski (zgodnie z kontrolą dokumentów ISO 10013).
* 📗 [Lessons Learned wg PMI](docs/LESSONS_LEARNED_PMI.md) – rejestr doświadczeń projektowych, sukcesy, wyzwania i rekomendacje (zgodnie z kontrolą dokumentów ISO 10013).

---

## 🤝 Współpraca (Contributing)

Zapraszamy do współtworzenia projektu! Zapoznaj się z [CONTRIBUTING.md](CONTRIBUTING.md) oraz [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## 👤 Autor

* **Zespół EcoBook Hub** – pomysł, implementacja, testy i dokumentacja

## ⚖️ Licencja

Projekt jest udostępniony na warunkach otwartej licencji [MIT](LICENSE). (c) 2026 EcoBook Hub.
