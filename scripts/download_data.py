import os
import sqlite3
from pathlib import Path

import pandas as pd
import requests

_PROJECT_ROOT = Path(__file__).resolve().parent.parent
DATA_DIR = os.getenv("DATA_DIR", str(_PROJECT_ROOT / "data"))
DB_PATH = os.path.join(DATA_DIR, "schools.sqlite")
EXCEL_PATH = os.path.join(DATA_DIR, "schools_temp.xlsx")
DATASET_RESOURCE_ID = os.getenv("DATASET_RESOURCE_ID", "1254769")
API_BASE_URL = os.getenv("API_BASE_URL", "https://api.dane.gov.pl/1.4")


def fetch_dane_gov_pl():
    os.makedirs(DATA_DIR, exist_ok=True)
    print("Pobieranie pliku Excel z API (ID 1254769)...")

    headers = {'User-Agent': 'EcoBookHub/1.0'}
    api_url = f"{API_BASE_URL}/resources/{DATASET_RESOURCE_ID}"

    resp = requests.get(api_url, headers=headers, timeout=15)
    resp.raise_for_status()

    file_url = resp.json().get('data', {}).get('attributes', {}).get('file_url')

    excel_resp = requests.get(file_url, headers=headers)
    excel_resp.raise_for_status()

    with open(EXCEL_PATH, 'wb') as f:
        f.write(excel_resp.content)

    print("Przetwarzanie danych przez Pandas...")
    df = pd.read_excel(EXCEL_PATH, engine='openpyxl')

    if "Wojewodztwo" in df.columns:
        df["Wojewodztwo"] = df["Wojewodztwo"].apply(normalize_wojewodztwo)

    print("Zasilanie bazy SQLite...")

    if os.path.exists(DB_PATH):
        os.remove(DB_PATH)

    con = sqlite3.connect(DB_PATH)
    con.execute('''
        CREATE TABLE schools (
            rspo TEXT,
            nazwa TEXT,
            wojewodztwo TEXT,
            powiat TEXT,
            miasto TEXT,
            typ TEXT
        )
    ''')
    con.execute('''
        CREATE TABLE listings (
            id INTEGER PRIMARY KEY,
            school_id TEXT,
            title TEXT,
            subject TEXT,
            price_pln REAL,
            condition TEXT,
            pickup_location TEXT,
            contact_info TEXT,
            image_bytes BLOB,
            klasa INTEGER,
            user_id INTEGER DEFAULT NULL,
            status TEXT DEFAULT 'active',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ''')

    mask = (
        df["Typ podmiotu"].astype(str).str.contains("Liceum", case=False, na=False)
        | df["Typ podmiotu"].astype(str).str.contains("Technikum", case=False, na=False)
        | df["Typ podmiotu"].astype(str).str.contains("Branżow", case=False, na=False)
    )
    filtered = df[mask]

    rows = []
    for _, r in filtered.iterrows():
        rspo = str(r.get("RSPO")) if pd.notna(r.get("RSPO")) else None
        ulica = r.get("Ulica")
        nazwa = str(r.get("Nazwa placówki"))
        if pd.notna(ulica):
            nazwa = nazwa + " (ul. " + str(ulica) + ")"
        woj = r.get("Wojewodztwo")
        powiat = r.get("Powiat")
        miasto = r.get("Miejscowość")
        typ = r.get("Typ podmiotu")
        rows.append((
            rspo,
            nazwa,
            None if pd.isna(woj) else str(woj),
            None if pd.isna(powiat) else str(powiat),
            None if pd.isna(miasto) else str(miasto),
            None if pd.isna(typ) else str(typ),
        ))

    con.executemany("INSERT INTO schools VALUES (?,?,?,?,?,?)", rows)
    con.commit()
    con.close()

    if os.path.exists(EXCEL_PATH):
        try:
            os.remove(EXCEL_PATH)
        except OSError:
            pass

    print("SUKCES: Baza danych gotowa.")


def normalize_wojewodztwo(value):
    """Normalizuje nazwę województwa do formatu Title Case."""
    if value is None:
        return None
    text = str(value).strip()
    if not text or text.lower() in {"nan", "none", ""}:
        return None
    return text.title()


if __name__ == '__main__':
    fetch_dane_gov_pl()


