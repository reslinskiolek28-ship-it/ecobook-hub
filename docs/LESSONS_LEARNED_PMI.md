# Rejestr Doświadczeń Projektowych (PMI Lessons Learned)

---

## 📋 Kontrola Dokumentu (zgodnie z ISO 10013)

| Parametr metryki | Wartość |
| :--- | :--- |
| **Identyfikator dokumentu** | `DOC-PMI-LL-01` |
| **Tytuł dokumentu** | Rejestr Doświadczeń i Dobrych Praktyk Projektowych (Lessons Learned) |
| **Wersja** | `1.0.0` |
| **Data wydania** | 2026-10-26 |
| **Autor** | Zespół EcoBook Hub |
| **Status dokumentu** | **Zatwierdzony** |
| **Metodyka bazowa** | PMI PMBOK® Guide – Knowledge Management & Lessons Learned |

### Historia zmian dokumentu

| Wersja | Data | Autor | Zakres zmian / Opis modyfikacji |
| :---: | :---: | :---: | :--- |
| `0.1.0` | 2026-10-22 | Zespół EcoBook Hub | Inicjalne zebranie uwag z fazy wdrożenia silnika DuckDB i testów Dockera. |
| `1.0.0` | 2026-10-26 | Zespół EcoBook Hub | Zatwierdzenie pełnego rejestru wniosków i zaleceń po zakończeniu projektu. |

---

## 1. Wprowadzenie i cel dokumentu

Zgodnie ze standardem Project Management Institute (PMI), rejestr *Lessons Learned* służy udokumentowaniu wiedzy zdobytej w trakcie cyklu życia projektu. Celem dokumentu jest identyfikacja sukcesów, wyzwań oraz sformułowanie rekomendacji technologicznych i zarządczych dla przyszłych zespołów pracujących z otwartymi danymi publicznymi (`dane.gov.pl`).

---

## 2. Co zadziałało dobrze? (What Went Well / Sukcesy)

### 2.1. Wybór stacku technologicznego (DuckDB + Streamlit)
* **Decyzja:** Zamiast tradycyjnej bazy relacyjnej (PostgreSQL/MySQL), postawiono na wbudowany silnik **DuckDB** operujący na pliku bazy `.duckdb`.
* **Efekt:**
  * Zerowe koszty infrastruktury – brak konieczności konfigurowania, zabezpieczania i opłacania serwera bazy danych.
  * Wykonywanie skomplikowanych zapytań SQL agregujących ponad 20 000 wierszy w czasie krótszym niż kilkadziesiąt milisekund.

### 2.2. Konteneryzacja i środowisko deweloperskie
* **Decyzja:** Zamontowanie wolumenów `./src:/app/src` oraz `./scripts:/app/scripts` w pliku `docker-compose.yml`.
* **Efekt:** Natychmiastowe odzwierciedlenie zmian w kodzie w przeglądarce bez konieczności restartowania lub przebudowywania obrazu Dockera, co skróciło czas iteracji deweloperskiej.

### 2.3. Automatyzacja jakości kodu (Pytest + Ruff + GitHub Actions)
* **Decyzja:** Wdrożenie testów jednostkowych badających zarówno logikę bazy danych, jak i normalizację danych oraz automatycznego lintera.
* **Efekt:** Szybkie wychwytywanie regresji (np. błędy importów, brakujące kolumny) w ułamki sekund przed trafieniem zmian do głównego repozytorium.
---

## 3. Co nie zadziałało? / Wyzwania i ryzyka (What Did Not Go Well)

### 3.1. Niekompatybilność DuckDB 0.9.2 z konwersją do pandas
* **Problem:** Wersja 0.9.2 biblioteki DuckDB w środowisku Windows powodowała błąd dostępu do pamięci (`access violation`) przy wywołaniu metody `.df()` na obiekcie relacji.
* **Przyczyna:** Błąd w natywnej bibliotece C++ DuckDB dla tej wersji i platformy.
* **Rozwiązanie:** Przejście na pobieranie wyników przez `.fetchall()` i jawne budowanie ramek `pandas.DataFrame` z listy krotek oraz nazw kolumn.

### 3.2. Niejednorodność i niska jakość danych wejściowych
* **Problem:** W oficjalnym rejestrze ministerialnym nazwy województw bywały zapisywane w różnych formatach (np. `MAZOWIECKIE`, `mazowieckie`), a separatory kolumn w pliku Excel bywały niespójne.
* **Rozwiązanie:** Napisanie funkcji `normalize_wojewodztwo` zabezpieczonej testami jednostkowymi.

### 3.3. Ograniczenia komponentów tabelarycznych Streamlit
* **Problem:** Domyślny komponent `st.dataframe` generował poziomy pasek przewijania przy standardowych rozdzielczościach ekranu.
* **Rozwiązanie:** Skrócenie etykiet oraz jawne zdefiniowanie parametrów kolumn.

---

## 4. Co zrobilibyśmy inaczej? (What We Would Do Differently)

1. **Ścisłe przypinanie wersji zależności (Dependency Pinning):**
   * W projektach produkcyjnych plik `requirements.txt` powinien zawsze definiować dokładne wersje, aby zapobiec niekontrolowanym aktualizacjom wprowadzającym *breaking changes*.
2. **Warstwa walidacji danych (np. Pydantic):**
   * Zamiast słowników wewnątrz pętli Excel, warto od początku zdefiniować model danych w Pydantic, co zapewniłoby automatyczną walidację typów.
3. **Pamięć podręczna (Caching) dla zapytań agregujących:**
   * Choć DuckDB jest niezwykle szybki, zapytania o listę województw czy statystyki ogólnokrajowe warto opleść dekoratorem `@st.cache_data`.

---

## 5. Rekomendacje dla przyszłych projektów (Recommendations)

* **Rekomendacja 1 (Dane):** Zawsze przygotowuj lekką próbkę danych dołączaną do repozytorium Gita – umożliwia to natychmiastowe uruchomienie testów jednostkowych w środowisku CI/CD bez konieczności pobierania pełnych zbiorów danych przez sieć.
* **Rekomendacja 2 (Architektura):** Wykorzystuj DuckDB wszędzie tam, gdzie rozmiar danych mieści się w pamięci RAM (do kilku-kilkunastu gigabajtów). Eliminuje to 90% problemów administracyjnych związanych z tradycyjnymi bazami SQL.
* **Rekomendacja 3 (Standardy):** Wdrożenie lintera `Ruff` zamiast starszych narzędzi (`flake8`, `isort`, `black`) pozwala zaoszczędzić czas – jest on ponad 10-krotnie szybszy i łatwiejszy w konfiguracji w `pyproject.toml`.
