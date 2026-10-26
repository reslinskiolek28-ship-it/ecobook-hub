# Historia zmian (Changelog)

Wszystkie istotne zmiany w tym projekcie będą dokumentowane w tym pliku.

Format opiera się na zasadach [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/),
a projekt stosuje wersjonowanie semantyczne [Semantic Versioning](https://semver.org/lang/pl/).

---

## [1.0.0] - 2026-10-26

### Dodano
* **Silnik bazy danych DuckDB**: Wdrożenie ultraszybkiego silnika in-memory do przetwarzania ponad 20 000 placówek z rejestru RSPO.
* **Interfejs Streamlit**: Intuicyjny panel z filtrami (województwo, powiat, miasto, szkoła).
* **Kluczowe metryki (KPI)**: Kafelki podsumowujące liczbę szkół, szacunkowe marnotrawstwo papieru i liczbę aktywnych ofert.
* **Wizualizacje analityczne**:
  * Wykres słupkowy liczby szkół per województwo.
  * Wykres top 10 powiatów.
* **Lokalna giełda P2P**: Możliwość wystawiania i przeglądania ofert podręczników w obrębie szkoły.
* **Pipeline danych (`scripts/download_data.py`)**: Automatyczne pobieranie rejestru z API `dane.gov.pl`, normalizacja i zapis do bazy DuckDB.
* **Pakiet testów jednostkowych (`tests/`)**: Testy w `pytest` weryfikujące schemat bazy, zapytania i normalizację danych.
* **Automatyzacja CI/CD**: Workflow GitHub Actions sprawdzający linter (`ruff`) i wykonujący testy przy każdym Pull Requeście.
* **Dokumentacja formalna**:
  * Raport końcowy oparty o standard ISO 21513 z kontrolą dokumentacji ISO 10013.
  * Rejestr Lessons Learned oparty o metodykę PMI z kontrolą dokumentacji ISO 10013.
  * Standardowe szablony zgłoszeń i Pull Requestów.
* **Konteneryzacja**: `Dockerfile` i `docker-compose.yml` umożliwiające uruchomienie jedną komendą.

### Naprawiono
* Błąd dostępu do pamięci w DuckDB 0.9.2 przy konwersji do pandas – zastąpiono `.df()` przez `.fetchall()` + `pandas.DataFrame`.
