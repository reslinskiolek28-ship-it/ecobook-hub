<?php
require __DIR__ . '/includes/db.php';

$action = $_REQUEST['action'] ?? '';

if ($action === 'image') {
    $id = (int)($_GET['id'] ?? 0);
    $st = db()->prepare("SELECT image_bytes FROM listings WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row && $row['image_bytes'] !== null) {
        $bytes = $row['image_bytes'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($bytes) ?: 'image/jpeg';
        header('Content-Type: ' . $mime);
        echo $bytes;
    } else {
        http_response_code(404);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_listing') {
        $school_id = $_POST['school_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $subject = $_POST['subject'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $condition = $_POST['condition'] ?? '';
        $pickup = trim($_POST['pickup'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $image_bytes = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_bytes = file_get_contents($_FILES['image']['tmp_name']);
        }
        if ($title !== '' && $contact !== '' && $school_id !== '') {
            add_listing($school_id, $title, $subject, $price, $condition, $pickup, $contact, $image_bytes);
        }
        $qs = http_build_query(['page' => 'gielda', 'woj' => $_POST['woj'] ?? '', 'powiat' => $_POST['powiat'] ?? '', 'miasto' => $_POST['miasto'] ?? '', 'school_id' => $school_id]);
        header('Location: ?' . $qs);
        exit;
    }
    if ($action === 'sold') {
        mark_listing_sold((int)($_POST['id'] ?? 0));
        $qs = http_build_query(['page' => 'gielda', 'woj' => $_POST['woj'] ?? '', 'powiat' => $_POST['powiat'] ?? '', 'miasto' => $_POST['miasto'] ?? '', 'school_id' => $_POST['school_id'] ?? '']);
        header('Location: ?' . $qs);
        exit;
    }
    if ($action === 'delete') {
        delete_listing((int)($_POST['id'] ?? 0));
        $qs = http_build_query(['page' => 'gielda', 'woj' => $_POST['woj'] ?? '', 'powiat' => $_POST['powiat'] ?? '', 'miasto' => $_POST['miasto'] ?? '', 'school_id' => $_POST['school_id'] ?? '']);
        header('Location: ?' . $qs);
        exit;
    }
}

$page = $_GET['page'] ?? 'raport';
$locked_target = $_GET['target'] ?? '';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EcoBook Hub</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root { --light-green: #39FF14; --dark-green: #006400; --mid-green: #228B22; --yellow: #FFFF00; --accent: #FFFFFF; --dark-bg: #070707; --panel-bg: #111111; }
* { box-sizing: border-box; }
body { background: radial-gradient(circle at top, #0f1f0f, #070707 60%); color: #E0E0E0; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 24px; }
.card { background: #141414; border: 1px solid #1f2f1f; border-left: 4px solid #39FF14; border-radius: 10px; padding: 16px; margin-bottom: 14px; box-shadow: 0 8px 24px rgba(0,0,0,0.35); transition: transform .15s ease, box-shadow .15s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(34,139,34,0.18); }
h1, h2, h3 { color: var(--light-green); font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
.metric-card { background-color: var(--panel-bg); border-top: 3px solid var(--mid-green); padding: 20px; border-radius: 4px; text-align: center; box-shadow: 0 4px 15px rgba(34,139,34,0.1); margin-bottom: 20px; }
.metric-value { font-size: 34px; font-weight: 900; color: var(--light-green); }
.metric-label { font-size: 13px; color: #AAA; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
button, .btn { background-color: transparent; color: var(--light-green); border: 1px solid var(--light-green); font-weight: bold; padding: 8px 14px; cursor: pointer; }
button.primary, .btn.primary { background-color: var(--light-green); color: #000; }
button:hover { background-color: var(--dark-green); color: #FFF; }
input, select { background-color: #1A1A1A; color: #FFF; border: 1px solid #333; border-radius: 0; padding: 6px; }
.card { border-left: 4px solid #39FF14; background-color: #111; padding: 15px; margin-bottom: 10px; }
.row { display: flex; gap: 10px; flex-wrap: wrap; }
.col { flex: 1; min-width: 200px; }
hr { border-color: #333; }
a { color: var(--light-green); }
img { max-width: 100%; }
</style>
</head>
<body>
<div class="row">
  <form method="get" style="flex:1"><input type="hidden" name="page" value="raport"><button type="submit" class="<?php echo $page==='raport'?'primary':''; ?>" style="width:100%">RAPORT BIZNESOWY I DANE SYSTEMOWE</button></form>
  <form method="get" style="flex:1"><input type="hidden" name="page" value="gielda"><button type="submit" class="<?php echo $page==='gielda'?'primary':''; ?>" style="width:100%">GIEŁDA LOKALNA (WYSZUKIWARKA)</button></form>
  <form method="get" style="flex:1"><input type="hidden" name="page" value="locked"><input type="hidden" name="target" value="biblioteka"><button type="submit" style="width:100%">🔒 BIBLIOTEKA</button></form>
  <form method="get" style="flex:1"><input type="hidden" name="page" value="locked"><input type="hidden" name="target" value="sklep"><button type="submit" style="width:100%">🔒 SKLEP ONLINE</button></form>
</div>
<br>
<?php if ($page === 'locked'): ?>
  <div class="card" style="text-align:center;padding:40px;">
    <h2>🔒 W TRAKCIE BUDOWY</h2>
    <p style="color:#aaa;font-size:16px;margin:12px 0 20px;">Zakładka <b><?php echo htmlspecialchars($locked_target === 'biblioteka' ? 'BIBLIOTEKA' : 'SKLEP ONLINE'); ?></b> jest obecnie w trakcie budowy. Zapraszamy wkrótce!</p>
    <form method="get"><input type="hidden" name="page" value="raport"><button type="submit" class="primary">⬅️ WRÓĆ DO RAPORTU</button></form>
  </div>
<?php elseif ($page === 'raport'): ?>
  <h1>ECOBOOK HUB // TERMINAL ANALITYCZNY</h1>
  <h3 style="color:#228B22 !important;font-size:1.2rem;">BAZA MINISTERSTWA EDUKACJI NARODOWEJ (RSPO)</h3>
  <hr>
  <?php
  try {
      [$total_high_schools, $total_listings, $df_woj, $df_types, $df_powiaty] = get_global_stats();
  } catch (Exception $e) {
      $total_high_schools = 0; $total_listings = 0; $df_woj = []; $df_types = []; $df_powiaty = [];
      echo '<p style="color:red">Brak bazy danych: '.htmlspecialchars($e->getMessage()).'</p>';
  }
  ?>
  <div class="row">
    <div class="col metric-card"><div class="metric-value"><?php echo (int)$total_high_schools; ?></div><div class="metric-label">SZKÓŁ / PLACÓWEK W BAZIE</div></div>
    <div class="col metric-card"><div class="metric-value"><?php echo (int)$total_listings; ?></div><div class="metric-label">AKTYWNYCH OGŁOSZEŃ</div></div>
  </div>
  <div class="row">
    <div class="col">
      <h3>SZKOŁY WG WOJEWÓDZTWA</h3>
      <div style="max-width:340px;margin:0 auto;"><canvas id="chartWoj"></canvas></div>
    </div>
    <div class="col">
      <h3>STRUKTURA TYPÓW SZKÓŁ</h3>
      <div style="max-width:300px;margin:0 auto;"><canvas id="chartTypes"></canvas></div>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <h3>TOP 10 POWIATÓW</h3>
      <div style="max-width:480px;margin:0 auto;"><canvas id="chartPow"></canvas></div>
    </div>
  </div>
  <script>
  const wojLabels = <?php echo json_encode(array_column($df_woj, 'wojewodztwo')); ?>;
  const wojData = <?php echo json_encode(array_map('intval', array_column($df_woj, 'liczba_szkol'))); ?>;
  new Chart(document.getElementById('chartWoj'), { type:'bar', data:{labels:wojLabels, datasets:[{label:'Liczba szkół', data:wojData, backgroundColor:'#39FF14'}]} });
  const typeLabels = <?php echo json_encode(array_column($df_types, 'typ')); ?>;
  const typeData = <?php echo json_encode(array_map('intval', array_column($df_types, 'count'))); ?>;
  const typeTotal = typeData.reduce((a,b)=>a+b,0);
  new Chart(document.getElementById('chartTypes'), {
    type: 'doughnut',
    data: { labels: typeLabels, datasets: [{ data: typeData, backgroundColor: ['#39FF14','#228B22','#006400','#FFFF00','#FFFFFF'], borderColor: '#070707', borderWidth: 2 }] },
    options: { plugins: { legend: { position: 'bottom', labels: { color: '#ccc', font: { size: 11 } } }, tooltip: { callbacks: { label: function(ctx){ const v = ctx.parsed; const p = typeTotal ? (v/typeTotal*100).toFixed(1) : 0; return ctx.label + ': ' + v + ' (' + p + '%)'; } } } }, cutout: '60%' }
  });
  const powLabels = <?php echo json_encode(array_column($df_powiaty, 'powiat')); ?>;
  const powData = <?php echo json_encode(array_map('intval', array_column($df_powiaty, 'liczba_szkol'))); ?>;
  new Chart(document.getElementById('chartPow'), { type:'bar', data:{labels:powLabels, datasets:[{label:'Liczba szkół', data:powData, backgroundColor:'#FFFF00'}]}, options:{indexAxis:'y'} });
  </script>
  <div style="text-align:center;margin-top:20px;">
    <form method="get"><input type="hidden" name="page" value="gielda"><button type="submit" class="primary">➡️ PRZEJDŹ DO GIEŁDY LOKALNEJ</button></form>
  </div>
<?php else: ?>
  <h1>GIEŁDA LOKALNA</h1>
  <?php
  $woj = $_GET['woj'] ?? '';
  $powiat = $_GET['powiat'] ?? '';
  $miasto = $_GET['miasto'] ?? '';
  $school_id = $_GET['school_id'] ?? '';
  $woj_options = get_voivodeships();
  $powiat_options = $woj !== '' ? get_powiaty($woj) : [];
  $city_options = $powiat !== '' ? get_cities($powiat) : [];
  $school_options = $miasto !== '' ? get_schools($miasto) : [];
  ?>
  <form method="get">
    <input type="hidden" name="page" value="gielda">
    <div class="row">
      <div class="col">
        <label>Województwo</label><br>
        <select name="woj" onchange="this.form.submit()">
          <option value="">-- wybierz --</option>
          <?php foreach ($woj_options as $w): ?>
            <option value="<?php echo htmlspecialchars($w); ?>" <?php echo $w===$woj?'selected':''; ?>><?php echo htmlspecialchars($w); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>Powiat</label><br>
        <select name="powiat" onchange="this.form.submit()">
          <option value="">-- wybierz --</option>
          <?php foreach ($powiat_options as $p): ?>
            <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $p===$powiat?'selected':''; ?>><?php echo htmlspecialchars($p); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>Miasto</label><br>
        <select name="miasto" onchange="this.form.submit()">
          <option value="">-- wybierz --</option>
          <?php foreach ($city_options as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $c===$miasto?'selected':''; ?>><?php echo htmlspecialchars($c); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col">
        <label>Szkoła</label><br>
        <select name="school_id" onchange="this.form.submit()">
          <option value="">-- wybierz --</option>
          <?php foreach ($school_options as $s): ?>
            <option value="<?php echo htmlspecialchars($s[0]); ?>" <?php echo $s[0]===$school_id?'selected':''; ?>><?php echo htmlspecialchars($s[1]); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </form>
  <hr>
  <?php if ($school_id !== ''): ?>
    <?php $listings = get_school_listings($school_id); ?>
    <h3>OFERTY W SZKOLE</h3>
    <?php if (empty($listings)): ?>
      <p>BAZA PUSTA. Brak aktywnych ofert na terenie tej placówki. Dodaj pierwszą książkę!</p>
    <?php else: ?>
      <?php foreach ($listings as $listing): ?>
        <div class="card">
          <h4 style="color:#FFF;margin:0"><?php echo htmlspecialchars($listing['title']); ?> <span style="color:#39FF14"><?php echo htmlspecialchars($listing['price_pln']); ?> PLN)</span></h4>
          <p style="color:#888;font-size:14px;margin-top:5px;">
            <b>Przedmiot:</b> <?php echo htmlspecialchars($listing['subject']); ?> | <b>Stan:</b> <?php echo htmlspecialchars($listing['condition']); ?> | <b>Punkt Odbioru:</b> <?php echo htmlspecialchars($listing['pickup_location']); ?><br>
            <b>Kontakt:</b> <span style="color:#FFF"><?php echo htmlspecialchars($listing['contact_info']); ?></span>
          </p>
          <?php if ($listing['image_bytes'] !== null): ?>
            <img src="?action=image&id=<?php echo (int)$listing['id']; ?>" width="250" alt="">
          <?php endif; ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="sold">
            <input type="hidden" name="id" value="<?php echo (int)$listing['id']; ?>">
            <input type="hidden" name="woj" value="<?php echo htmlspecialchars($woj); ?>">
            <input type="hidden" name="powiat" value="<?php echo htmlspecialchars($powiat); ?>">
            <input type="hidden" name="miasto" value="<?php echo htmlspecialchars($miasto); ?>">
            <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school_id); ?>">
            <button type="submit">Oznacz jako sprzedane</button>
          </form>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)$listing['id']; ?>">
            <input type="hidden" name="woj" value="<?php echo htmlspecialchars($woj); ?>">
            <input type="hidden" name="powiat" value="<?php echo htmlspecialchars($powiat); ?>">
            <input type="hidden" name="miasto" value="<?php echo htmlspecialchars($miasto); ?>">
            <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school_id); ?>">
            <button type="submit">Usuń ofertę</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <hr>
    <h3>+ WYSTAW SWÓJ PODRĘCZNIK</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_listing">
      <input type="hidden" name="woj" value="<?php echo htmlspecialchars($woj); ?>">
      <input type="hidden" name="powiat" value="<?php echo htmlspecialchars($powiat); ?>">
      <input type="hidden" name="miasto" value="<?php echo htmlspecialchars($miasto); ?>">
      <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school_id); ?>">
      <div class="row">
        <div class="col">
          <label>Tytuł i Wydawnictwo</label><br>
          <input name="title" style="width:100%"><br><br>
          <label>Przedmiot</label><br>
          <select name="subject" style="width:100%">
            <?php foreach (["Matematyka","J. Polski","J. Angielski","Fizyka","Chemia","Biologia","Geografia","Historia","Informatyka","Zawodowe","Inne"] as $s): ?>
              <option><?php echo $s; ?></option>
            <?php endforeach; ?>
          </select><br><br>
          <label>Cena (PLN)</label><br>
          <input type="number" name="price" min="0" step="1" style="width:100%">
        </div>
        <div class="col">
          <label>Stan Książki</label><br>
          <select name="condition" style="width:100%">
            <?php foreach (["Jak Nowy","Używany (Drobne Ślady)","Używany (Pozakreślany)"] as $c): ?>
              <option><?php echo $c; ?></option>
            <?php endforeach; ?>
          </select><br><br>
          <label>Gdzie w szkole</label><br>
          <input name="pickup" style="width:100%"><br><br>
          <label>Kontakt (Tel / Messenger)</label><br>
          <input name="contact" style="width:100%"><br><br>
          <label>Zdjęcie (opcjonalnie)</label><br>
          <input type="file" name="image" accept="image/png,image/jpeg">
        </div>
      </div>
      <br>
      <button type="submit" class="primary">>> ZAINICJUJ OFERTĘ W SZKOLE <<</button>
    </form>
  <?php endif; ?>
  <div style="text-align:center;margin-top:20px;">
    <form method="get"><input type="hidden" name="page" value="raport"><button type="submit" class="primary">⬅️ WRÓĆ DO RAPORTU</button></form>
  </div>
<?php endif; ?>
</body>
</html>
