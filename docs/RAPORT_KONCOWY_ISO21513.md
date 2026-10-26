# Raport Końcowy Projektu (ISO 21513)

---

## 📋 Kontrola Dokumentu (zgodnie z ISO 10013)

| Parametr metryki | Wartość |
| :--- | :--- |
| **Identyfikator dokumentu** | `DOC-ISO21513-REP-01` |
| **Tytuł dokumentu** | Raport Końcowy z Realizacji Projektu: EcoBook Hub – Otwarta Giełda Podręczników Szkolnych |
| **Wersja** | `1.0.0` |
| **Data wydania** | 2026-10-26 |
| **Autor** | Zespół EcoBook Hub |
| **Status dokumentu** | **Zatwierdzony** |
| **Klasyfikacja** | Publiczny (Open Source) |

### Historia zmian dokumentu

| Wersja | Data | Autor | Zakres zmian / Opis modyfikacji |
| :---: | :---: | :---: | :--- |
| `0.1.0` | 2026-10-22 | Zespół EcoBook Hub | Wstępny szkic założeń projektowych i analiza źródeł danych RSPO. |
| `0.9.0` | 2026-10-23 | Zespół EcoBook Hub | Uzupełnienie wyników testów, wdrożenia silnika DuckDB oraz interfejsu Streamlit. |
| `1.0.0` | 2026-10-26 | Zespół EcoBook Hub | Wersja finalna raportu podsumowująca wdrożenie i wpływ społeczny. |

---

## 1. Cel projektu (Project Purpose & Objectives)

Głównym celem projektu było stworzenie nowoczesnej, ogólnodostępnej aplikacji open source, która przekształca surowe, trudnodostępne dla przeciętnego obywatela dane publiczne z portalu `dane.gov.pl` w intuicyjne narzędzie rozwiązujące realny problem społeczny.

### Konkretny problem społeczny:
Koszt nowych podręczników szkolnych stanowi znaczne obciążenie dla domowych budżetów. Oficjalne dane Ministerstwa Edukacji (Rejestr Szkół i Placówek Oświatowych – RSPO) są publicznie dostępne, jednak publikowane w formie surowych plików Excel liczących tysiące rekordów. Rodzice i uczniowie napotykają na bariery:
* Brak możliwości szybkiego znalezienia szkół w swojej okolicy.
* Brak scentralizowanej, lokalnej giełdy używanych podręczników powiązanej z konkretną placówką.
* Trudność w oszacowaniu skali szkolnictwa w regionie i potencjalnej oszczędności papieru.

### Cele szczegółowe:
1. **Technologiczne:** Zbudowanie wydajnej architektury bezserwerowej opartej o DuckDB i relacyjne zapytania SQL, zapewniającej natychmiastowe czasy odpowiedzi bez konieczności kosztownego hostingu baz danych.
2. **Użytkowe:** Dostarczenie responsywnego interfejsu (Streamlit) z kaskadowymi filtrami geograficznymi (województwo, powiat, miasto, szkoła) oraz lokalną giełdą P2P.
3. **Jakościowe:** Wdrożenie automatycznego pipeline'u CI/CD, pokrycia testami jednostkowymi i rygorystycznych standardów czystości kodu.

---

## 2. Rezultaty i wdrożone rozwiązania (Project Deliverables)

W ramach projektu zrealizowano w 100% założony zakres prac:

1. **Pipeline ekstrakcji i transformacji danych (`scripts/download_data.py`):**
   * Automatyczna komunikacja z REST API `dane.gov.pl` (zasób ID 1254769).
   * Parsowanie rejestru, normalizacja województw oraz filtrowanie placówek średnich (licea, technika, szkoły branżowe).
   * Zapis do osadzonej bazy DuckDB (`data/schools.duckdb`) wraz z tabelą ofert `listings`.
2. **Silnik analityczny DuckDB:**
   * Wdrożenie zapytań in-memory pozwalających na agregację i filtrowanie ponad 20 000 placówek w czasie poniżej kilkudziesięciu milisekund.
3. **Interfejs Streamlit (`src/app.py`):**
   * Dynamiczne filtry: Województwo, Powiat, Miasto, Szkoła.
   * Kafelki metryk KPI (Zindeksowane szkoły, Szacunkowe marnotrawstwo papieru, Aktywne oferty P2P).
   * Interaktywne wykresy analityczne (szkoły per województwo, top powiaty).
   * Lokalna giełda podręczników z możliwością dodawania ofert (zdjęcie, cena, stan, kontakt).
4. **Konteneryzacja i łatwy deploy:**
   * Obraz Docker (`Dockerfile`) i konfiguracja `docker-compose.yml` pozwalająca uruchomić aplikację jedną komendą: `docker compose up --build`.
5. **Jakość kodu i CI/CD:**
   * Testy jednostkowe `pytest` w katalogu `tests/`.
   * Linter `Ruff` skonfigurowany w `pyproject.toml`.
   * GitHub Actions workflow (`.github/workflows/ci.yml`) zabezpieczający jakość każdego Pull Requesta.

---

## 3. Porównanie z pierwotnym planem (Plan vs Actual)

| Obszar | Plan pierwotny | Stan zrealizowany | Odchylenie / Komentarz |
| :--- | :--- | :--- | :--- |
| **Format danych** | CSV / baza danych | DuckDB (osadzona) | Zrealizowano z optymalizacją. Zrezygnowano z ciężkiego serwera bazy danych na rzecz osadzonego DuckDB. |
| **Filtry** | Podstawowe (nazwa) | Rozszerzone o województwa, powiaty, miasta i szkoły | Dodano dodatkową wartość analityczną po analizie rejestru RSPO. |
| **Wykresy** | Prosta tabela | Kafelki KPI + 2 wykresy słupkowe | Rozszerzono o analizę regionalną i top powiaty. |
| **Wdrożenie** | Instrukcja instalacji | Docker Compose (1-click) | Zgodne z najwyższym kryterium łatwości uruchomienia. |
| **Harmonogram** | 1 tydzień | Zrealizowano w założonym oknie czasowym | Wszystkie kryteria spełnione w terminie. |

---

## 4. Wpływ społeczny i wartość dodana (Social Impact)

Aplikacja dostarcza bezpośrednich korzyści dla trzech grup interesariuszy:

1. **Rodzice i uczniowie:**
   * Oszczędność budżetu: Możliwość zakupu używanych podręczników bezpośrednio w swojej szkole (P2P).
   * Oszczędność czasu: Zamiast przeszukiwać dziesiątki ogłoszeń, rodzic w kilka sekund weryfikuje oferty na terenie własnej placówki.
2. **Szkoły i społeczność lokalna:**
   * Cyfryzacja giełdy szkolnej bez konieczności wdrażania kosztownych systemów.
   * Wsparcie gospodarki obiegu zamkniętego i redukcja marnotrawstwa papieru.
3. **Społeczność Open Source:**
   * Projekt stanowi wzorcowy szablon jak budować ultraszybkie, bezkosztowe w utrzymaniu narzędzia oparte o DuckDB, Streamlit i otwarte dane publiczne.

---

## 5. Wnioski końcowe i rekomendacje (Conclusions & Outlook)

Projekt zakończył się pełnym sukcesem operacyjnym i merytorycznym. Wykorzystanie DuckDB w połączeniu z danymi RSPO okazało się idealnym rozwiązaniem dla tego typu problemów – zapewnia wydajność systemów klasy enterprise przy zerowych kosztach infrastrukturalnych. Zidentyfikowano również ograniczenie biblioteki DuckDB w wersji 0.9.2 (metoda `.df()` powodowała błąd dostępu do pamięci w środowisku Windows), co rozwiązano przez jawne budowanie ramek `pandas.DataFrame` z wyników `fetchall()`.

### Rekomendacje dotyczące dalszego rozwoju:
* **Geolokalizacja i mapa:** Rozszerzenie dashboardu o komponent mapowy bazujący na adresach placówek.
* **Powiadomienia:** Możliwość subskrypcji alertów e-mail w momencie pojawienia się nowej oferty w wybranej szkole.
* **Moderacja:** Wdrożenie prostego systemu weryfikacji ofert przez administrację szkoły.