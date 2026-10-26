from scripts.download_data import normalize_wojewodztwo


def test_normalize_wojewodztwo():
    assert normalize_wojewodztwo("mazowieckie") == "Mazowieckie"
    assert normalize_wojewodztwo("  ŚLĄSKIE  ") == "Śląskie"
    assert normalize_wojewodztwo(None) is None
    assert normalize_wojewodztwo("") is None
    assert normalize_wojewodztwo("nan") is None
