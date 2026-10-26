# Zasady współtworzenia projektu (Contributing Guide)

Dziękujemy za zainteresowanie rozwojem projektu **EcoBook Hub**! Poniższy dokument opisuje wytyczne dotyczące zgłaszania błędów, proponowania nowych funkcji oraz przesyłania zmian do kodu.

---

## 📜 Kodeks postępowania

Wszyscy uczestnicy społeczności są zobowiązani do przestrzegania zasad opisanych w pliku [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

---

## 🐞 Zgłaszanie błędów (Bug Reports)

Przed utworzeniem nowego zgłoszenia:
1. Sprawdź w zakładce [Issues](https://github.com/twoj-login/ecobook-hub/issues), czy dany problem nie został już zgłoszony.
2. Jeśli problem jest nowy, skorzystaj z dedykowanego szablonu zgłoszenia błędu.
3. W zgłoszeniu opisz dokładnie:
   * Kroki do odtworzenia problemu.
   * Oczekiwany vs rzeczywisty rezultat.
   * Twoje środowisko (wersja Dockera, OS, przeglądarka).

---

## 💡 Proponowanie usprawnień (Feature Requests)

Chętnie przyjmujemy pomysły na nowe metryki, filtry czy analizy danych:
1. Utwórz zgłoszenie w oparciu o szablon propozycji funkcji.
2. Wyjaśnij, w jaki sposób proponowane usprawnienie pomoże rodzicom lub uczniom (wpływ społeczny).

---

## 🛠️ Rozwój lokalny i proces Pull Request (PR)

### 1. Przygotowanie środowiska
Sklonuj repozytorium i utwórz własną gałąź roboczą (*branch*):
```bash
git checkout -b feature/nazwa-twojej-funkcji
```

Zainstaluj zależności produkcyjne oraz deweloperskie:
```bash
pip install -r requirements.txt
pip install -r requirements-dev.txt
```

### 2. Standardy jakości kodu
Przed zgłoszeniem Pull Requesta upewnij się, że:
1. **Linter nie zgłasza uwag:**
   ```bash
   ruff check .
   ```
2. **Wszystkie testy przechodzą pomyślnie:**
   ```bash
   pytest -v tests/
   ```
3. Do kodu dodano nowe testy jednostkowe pokrywające wprowadzone zmiany.
4. **Higiena danych:** Do commitów **nie wolno** dodawać pełnych baz danych (`schools.duckdb`). Używaj wyłącznie małych próbek testowych.

### 3. Wysłanie Pull Requesta
* Nadaj swojemu PR przejrzysty tytuł.
* Wypełnij szablon Pull Requesta, opisując cel zmian i zaznaczając odpowiednie punkty listy kontrolnej.
* Upewnij się, że automatyczny pipeline CI na GitHubie zakończył się sukcesem (zielony status).
