import duckdb
import os

DB_PATH = os.path.join('data', 'schools.duckdb')

def get_connection():
    return duckdb.connect(DB_PATH, read_only=True)

def get_voivodeships(con):
    res = con.execute("SELECT DISTINCT wojewodztwo FROM schools WHERE wojewodztwo IS NOT NULL ORDER BY wojewodztwo").fetchall()
    return [r[0] for r in res if r[0]]

def get_powiaty(con, woj):
    res = con.execute("SELECT DISTINCT powiat FROM schools WHERE wojewodztwo = ? AND powiat IS NOT NULL ORDER BY powiat", [woj]).fetchall()
    return [r[0] for r in res if r[0]]

def get_cities(con, powiat):
    res = con.execute("SELECT DISTINCT miasto FROM schools WHERE powiat = ? AND miasto IS NOT NULL ORDER BY miasto", [powiat]).fetchall()
    return [r[0] for r in res if r[0]]

def get_schools(con, miasto):
    res = con.execute("SELECT rspo, nazwa FROM schools WHERE miasto = ? ORDER BY nazwa", [miasto]).fetchall()
    return res

def get_school_listings(con, school_id):
    query = '''SELECT title, subject, price_pln, condition, pickup_location, contact_info, image_bytes, created_at 
               FROM listings WHERE school_id = ? ORDER BY created_at DESC'''
    return con.execute(query, [school_id]).fetchall()

def add_listing(db_path, school_id, title, subject, price, condition, pickup, contact, image_bytes):
    con_rw = duckdb.connect(db_path)
    query = '''INSERT INTO listings (id, school_id, title, subject, price_pln, condition, pickup_location, contact_info, image_bytes)
               VALUES ((SELECT COALESCE(MAX(id), 0) + 1 FROM listings), ?, ?, ?, ?, ?, ?, ?, ?)'''
    con_rw.execute(query, [school_id, title, subject, price, condition, pickup, contact, image_bytes])
    con_rw.close()

def get_global_stats(con):
    total_high_schools = con.execute("SELECT COUNT(*) FROM schools").fetchone()[0]
    total_listings = con.execute("SELECT COUNT(*) FROM listings").fetchone()[0]
    
    df_woj = con.execute('''
        SELECT wojewodztwo, COUNT(*) as liczba_szkol, COUNT(*) * 2.5 as zuzycie_ton 
        FROM schools GROUP BY wojewodztwo ORDER BY liczba_szkol DESC
    ''').df()
    
    df_types = con.execute("SELECT typ, COUNT(*) as count FROM schools GROUP BY typ").df()
    
    # Przebudowane na Powiaty aby poprawnie grupowac miasta na prawach powiatu (np. m. st. Warszawa)
    df_powiaty = con.execute('''
        SELECT powiat, COUNT(*) as liczba_szkol 
        FROM schools GROUP BY powiat ORDER BY liczba_szkol DESC LIMIT 10
    ''').df()
    
    return total_high_schools, total_listings, df_woj, df_types, df_powiaty