<?php
// includes/db.php
function db_path(): string {
    $dataDir = getenv('DATA_DIR');
    if ($dataDir && $dataDir !== '') {
        return rtrim($dataDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'schools.sqlite';
    }
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'schools.sqlite';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $path = db_path();
        if (!file_exists($path)) {
            throw new RuntimeException('Brak bazy danych: ' . $path);
        }
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function get_voivodeships(): array {
    $rows = db()->query("SELECT DISTINCT wojewodztwo FROM schools WHERE wojewodztwo IS NOT NULL ORDER BY wojewodztwo")->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        if ($r['wojewodztwo'] !== null && $r['wojewodztwo'] !== '') {
            $out[] = $r['wojewodztwo'];
        }
    }
    sort($out);
    return $out;
}

function get_powiaty(string $woj): array {
    $st = db()->prepare("SELECT DISTINCT powiat FROM schools WHERE wojewodztwo = ? AND powiat IS NOT NULL ORDER BY powiat");
    $st->execute([$woj]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        if ($r['powiat'] !== null && $r['powiat'] !== '') {
            $out[] = $r['powiat'];
        }
    }
    return $out;
}

function get_cities(string $powiat): array {
    $st = db()->prepare("SELECT DISTINCT miasto FROM schools WHERE powiat = ? AND miasto IS NOT NULL ORDER BY miasto");
    $st->execute([$powiat]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        if ($r['miasto'] !== null && $r['miasto'] !== '') {
            $out[] = $r['miasto'];
        }
    }
    return $out;
}

function get_schools(string $miasto): array {
    $st = db()->prepare("SELECT rspo, nazwa FROM schools WHERE miasto = ? ORDER BY nazwa");
    $st->execute([$miasto]);
    return $st->fetchAll(PDO::FETCH_NUM);
}

function get_school_listings($school_id, $search = ''): array {
    $pdo = db();
    $sql = "SELECT id, title, subject, price_pln, condition, pickup_location, contact_info, image_bytes, created_at, status, klasa FROM listings WHERE school_id = ? AND status = 'active'";
    $params = [$school_id];
    if ($search !== '') {
        $sql .= " AND (title LIKE ? OR subject LIKE ? OR CAST(klasa AS TEXT) LIKE ?)";
        $like = "%" . $search . "%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= " ORDER BY created_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function mark_listing_sold($id): void {
    $st = db()->prepare("UPDATE listings SET status = 'sold' WHERE id = ?");
    $st->execute([$id]);
}

function delete_listing($id): void {
    $st = db()->prepare("DELETE FROM listings WHERE id = ?");
    $st->execute([$id]);
}

function add_listing($school_id, $title, $subject, $price, $condition, $pickup, $contact, $image_bytes, $klasa = null): void {
    $pdo = db();
    $st = $pdo->prepare("INSERT INTO listings (id, school_id, title, subject, price_pln, condition, pickup_location, contact_info, image_bytes, klasa) VALUES ((SELECT COALESCE(MAX(id), 0) + 1 FROM listings), :school_id, :title, :subject, :price, :condition, :pickup, :contact, :image, :klasa)");
    $st->bindValue(':school_id', $school_id);
    $st->bindValue(':title', $title);
    $st->bindValue(':subject', $subject);
    $st->bindValue(':price', $price);
    $st->bindValue(':condition', $condition);
    $st->bindValue(':pickup', $pickup);
    $st->bindValue(':contact', $contact);
    if ($image_bytes === null) {
        $st->bindValue(':image', null, PDO::PARAM_NULL);
    } else {
        $st->bindValue(':image', $image_bytes, PDO::PARAM_LOB);
    }
    if ($klasa === null || $klasa === '') {
        $st->bindValue(':klasa', null, PDO::PARAM_NULL);
    } else {
        $st->bindValue(':klasa', (int)$klasa, PDO::PARAM_INT);
    }
    $st->execute();
}

function get_global_stats(): array {
    $pdo = db();
    $total_schools = (int)$pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
    $total_listings = (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();

    $woj = $pdo->query("SELECT wojewodztwo, COUNT(*) as liczba_szkol, COUNT(*) * 2.5 as zuzycie_ton FROM schools GROUP BY wojewodztwo ORDER BY liczba_szkol DESC")->fetchAll();
    $types = $pdo->query("SELECT typ, COUNT(*) as count FROM schools GROUP BY typ")->fetchAll();
    $powiaty = $pdo->query("SELECT powiat, COUNT(*) as liczba_szkol FROM schools GROUP BY powiat ORDER BY liczba_szkol DESC LIMIT 10")->fetchAll();

    return [$total_schools, $total_listings, $woj, $types, $powiaty];
}
