import os
import requests
import duckdb
import pandas as pd

DATA_DIR = 'data'
DB_PATH = os.path.join(DATA_DIR, 'schools.duckdb')
EXCEL_PATH = os.path.join(DATA_DIR, 'schools_temp.xlsx')

def fetch_dane_gov_pl():
    os.makedirs(DATA_DIR, exist_ok=True)
    print("Pobieranie pliku Excel z API (ID 1254769)...")
    
    headers = {'User-Agent': 'EcoBookHub/1.0'}
    api_url = "https://api.dane.gov.pl/1.4/resources/1254769"
    
    resp = requests.get(api_url, headers=headers, timeout=15)
    resp.raise_for_status()
    
    file_url = resp.json().get('data', {}).get('attributes', {}).get('file_url')
    
    excel_resp = requests.get(file_url, headers=headers)
    excel_resp.raise_for_status()
    
    with open(EXCEL_PATH, 'wb') as f:
        f.write(excel_resp.content)

    print("Przetwarzanie danych przez Pandas...")
    df = pd.read_excel(EXCEL_PATH, engine='openpyxl')
    
    if 'Wojewodztwo' in df.columns:
        df['Wojewodztwo'] = df['Wojewodztwo'].astype(str).str.title()

    print("Zasilanie bazy DuckDB...")
    con = duckdb.connect(DB_PATH)
    
    con.execute('''
        CREATE OR REPLACE TABLE schools AS 
        SELECT 
            CAST(RSPO AS VARCHAR) as rspo,
            "Nazwa placówki" || COALESCE(' (ul. ' || CAST(Ulica AS VARCHAR) || ')', '') as nazwa,
            Wojewodztwo as wojewodztwo,
            Powiat as powiat,
            "Miejscowość" as miasto,
            "Typ podmiotu" as typ
        FROM df 
        WHERE "Typ podmiotu" ILIKE '%Liceum%' 
           OR "Typ podmiotu" ILIKE '%Technikum%' 
           OR "Typ podmiotu" ILIKE '%Branżow%'
    ''')
    
    con.execute('''
        CREATE OR REPLACE TABLE listings (
            id INTEGER,
            school_id VARCHAR,
            title VARCHAR,
            subject VARCHAR,
            price_pln DECIMAL(10,2),
            condition VARCHAR,
            pickup_location VARCHAR,
            contact_info VARCHAR,
            image_bytes BLOB,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    con.close()
    
    if os.path.exists(EXCEL_PATH):
        os.remove(EXCEL_PATH)
        
    print("SUKCES: Baza danych gotowa.")

if __name__ == '__main__':
    fetch_dane_gov_pl()