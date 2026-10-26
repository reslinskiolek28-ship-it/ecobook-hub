import duckdb
import pytest

from db import (
    add_listing,
    get_cities,
    get_connection,
    get_global_stats,
    get_powiaty,
    get_school_listings,
    get_schools,
    get_voivodeships,
)


@pytest.fixture
def sample_db(tmp_path):
    db_path = tmp_path / "sample.duckdb"
    con = duckdb.connect(str(db_path))
    con.execute(
        """
        CREATE TABLE schools (
            rspo VARCHAR,
            nazwa VARCHAR,
            wojewodztwo VARCHAR,
            powiat VARCHAR,
            miasto VARCHAR,
            typ VARCHAR
        )
        """
    )
    con.execute(
        """
        INSERT INTO schools VALUES
        ('1', 'Liceum A (ul. X)', 'Mazowieckie', 'Warszawa', 'Warszawa', 'Liceum'),
        ('2', 'Technikum B', 'Mazowieckie', 'Warszawa', 'Warszawa', 'Technikum'),
        ('3', 'Branżowa C', 'Śląskie', 'Katowice', 'Katowice', 'Branżowa')
        """
    )
    con.execute(
        """
        CREATE TABLE listings (
            id INTEGER,
            school_id VARCHAR,
            title VARCHAR,
            subject VARCHAR,
            price_pln DECIMAL(10,2),
            condition VARCHAR,
            pickup_location VARCHAR,
            contact_info VARCHAR,
            image_bytes BLOB,
            status VARCHAR DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        """
    )
    con.execute(
        """
        INSERT INTO listings (id, school_id, title, subject, price_pln, condition, pickup_location, contact_info, image_bytes, status)
        VALUES (1, '1', 'Matematyka 1', 'Matematyka', 50.0, 'Jak Nowy', 'Szafki', 'tel', NULL, 'active')
        """
    )
    con.close()
    return db_path


def test_voivodeships(sample_db, monkeypatch):
    import db

    monkeypatch.setattr(db, "DB_PATH", str(sample_db))
    con = get_connection()
    assert get_voivodeships(con) == ["Mazowieckie", "Śląskie"]
    con.close()


def test_powiaty_and_cities(sample_db):
    con = duckdb.connect(str(sample_db), read_only=True)
    assert get_powiaty(con, "Mazowieckie") == ["Warszawa"]
    assert get_cities(con, "Warszawa") == ["Warszawa"]
    assert get_schools(con, "Warszawa") == [("1", "Liceum A (ul. X)"), ("2", "Technikum B")]
    con.close()


def test_listings_roundtrip(sample_db):
    add_listing(
        str(sample_db),
        "1",
        "Fizyka 1",
        "Fizyka",
        30.0,
        "Używany",
        "Biblioteka",
        "mail",
        None,
    )
    con = duckdb.connect(str(sample_db), read_only=True)
    rows = get_school_listings(con, "1")
    assert len(rows) == 2
    con.close()


def test_global_stats(sample_db):
    con = duckdb.connect(str(sample_db), read_only=True)
    total_schools, total_listings, df_woj, df_types, df_powiaty = get_global_stats(con)
    assert total_schools == 3
    assert total_listings == 1
    assert not df_woj.empty
    con.close()
