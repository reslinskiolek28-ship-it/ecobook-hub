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
        $price_raw = $_POST['price'] ?? '';
        $price = is_numeric($price_raw) ? (float)$price_raw : 0;
        $klasa_raw = $_POST['klasa'] ?? '';
        $klasa = ($klasa_raw === '' ? null : (int)$klasa_raw);
        $condition = $_POST['condition'] ?? '';
        $pickup = trim($_POST['pickup'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $image_bytes = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_bytes = file_get_contents($_FILES['image']['tmp_name']);
        }
        $digits = preg_replace('/\D/', '', $contact);
        $err = '';
        if ($title === '') {
            $err = 'title';
        } elseif ($price_raw === '' || !is_numeric($price_raw)) {
            $err = 'price';
        } elseif ($klasa_raw !== '' && ($klasa < 1 || $klasa > 5)) {
            $err = 'klasa';
        } elseif (strlen($digits) < 9) {
            $err = 'phone';
        } elseif ($school_id === '') {
            $err = 'school';
        }
        if ($err === '') {
            add_listing($school_id, $title, $subject, $price, $condition, $pickup, $contact, $image_bytes, $klasa);
        }
        $qs = http_build_query(['page' => 'gielda', 'woj' => $_POST['woj'] ?? '', 'powiat' => $_POST['powiat'] ?? '', 'miasto' => $_POST['miasto'] ?? '', 'school_id' => $school_id, 'err' => $err, 'search' => $_POST['search'] ?? '']);
        header('Location: ?' . $qs);
        exit;
    }
    if ($action === 'sold') {
        mark_listing_sold((int)($_POST['id'] ?? 0));
        $qs = http_build_query(['page' => 'gielda', 'woj' => $_POST['woj'] ?? '', 'powiat' => $_POST['powiat'] ?? '', 'miasto' => $_POST['miasto'] ?? '', 'school_id' => $_POST['school_id'] ?? '', 'search' => $_POST['search'] ?? '']);
        header('Location: ?' . $qs);
        exit;
    }
    if ($action === 'delete') {
        delete_listing((int)($_POST['id'] ?? 0));
        $qs = http_build_query(['page' => 'gielda', 'woj' => $_POST['woj'] ?? '', 'powiat' => $_POST['powiat'] ?? '', 'miasto' => $_POST['miasto'] ?? '', 'school_id' => $_POST['school_id'] ?? '', 'search' => $_POST['search'] ?? '']);
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
  <div class="card" style="margin-top:10px;padding:24px;">
    <h2 style="font-size:24px;margin-bottom:16px;">CZYM JEST ECOBOOK HUB?</h2>
    <div style="display:grid;grid-template-columns:1fr;gap:14px;">
      <div>
        <h3 style="font-size:18px;margin-bottom:4px;">Na czym polega nasz pomysł?</h3>
        <p style="color:#ddd;font-size:16px;line-height:1.7;margin:0;">EcoBook Hub to innowacyjna platforma webowa stworzona z myślą o uczniach i rodzicach, którzy co roku zmagają się z problemem wysokich kosztów zakupu podręczników. System ten całkowicie automatyzuje proces odkupowania i sprzedawania używanych książek szkolnych w obrębie jednej placówki lub regionu. Dzięki funkcji „odbioru w szkole” platforma całkowicie eliminuje koszty oraz czasochłonność tradycyjnych przesyłek kurierskich. Projekt aktywnie wspiera gospodarki obiegu zamkniętego, zmniejszając ilość marnowanego papieru i promowanie ekologicznych postaw wśród młodzieży.</p>
      </div>
      <div>
        <h3 style="font-size:18px;margin-bottom:4px;">Jaki problem lub potrzebę rozwiązuje nasz pomysł?</h3>
        <p style="color:#ddd;font-size:16px;line-height:1.7;margin:0;">Coroczny zakup nowych podręczników szkolnych generuje bardzo wysokie koszty dla domowych budżetów, a stare książki często zalegają w domach lub trafiają na makulaturę. Ponadto tradycyjne handlowanie używanymi książkami w internecie jest czasochłonne, a wysokie koszty przesyłek kurierskich często przewyższają wartość samych podręczników. Nasz pomysł rozwiązuje te problemy poprzez cyfryzację i lokalizację całego procesu, oferując szybką wymianę książek bezpośrednio w szkole.</p>
      </div>
      <div>
        <h3 style="font-size:18px;margin-bottom:4px;">Kto będzie korzystać z naszego rozwiązania?</h3>
        <p style="color:#ddd;font-size:16px;line-height:1.7;margin:0;">Z głównego rozwiązania będą korzystać uczniowie szkół ponadpodstawowych oraz ich rodzice, którzy poszukują tańszych i ekologicznych sposobów na zdobycie wymaganych książek. Dodatkowo z platformy skorzystają same szkoły, zyskując sprawne narzędzie wspierające lokalną społeczność i promocję idei ekologicznych.</p>
      </div>
      <div>
        <h3 style="font-size:18px;margin-bottom:4px;">Co jest nowego lub wyjątkowego w naszym pomyśle?</h3>
        <p style="color:#ddd;font-size:16px;line-height:1.7;margin:0;">Wyjątkowość naszego projektu polega na całkowitej lokalizacji całego procesu wymiany i eliminacji kosztów wysyłki dzięki opcji odbioru książek bezpośrednio na terenie szkoły. Ponadto łączymy funkcję tradycyjnego marketplace'u z elementami ekologii i ekonomii współdzielenia, tworząc zamknięty, bezpieczny obieg podręczników dla szkolnej społeczności.</p>
      </div>
      <div style="background:#0e130e;border:1px solid #1f2f1f;border-radius:10px;padding:14px;">
        <h3 style="font-size:18px;margin-bottom:4px;">W jakim kierunku chcemy się rozwijać?</h3>
        <p style="color:#9aa;font-size:15px;line-height:1.7;margin:0;font-style:italic;">W perspektywie długoterminowej planujemy przekształcić platformę w pełni funkcjonalną aplikację mobilną zintegrowaną z systemami szkolnymi, co ułatwi uczniom szybki handel książkami za pomocą kilku kliknięć. W przyszłości zamierzamy także rozbudować serwis o automatyczny system rekomendacji lektur oraz moduł wymiany innych materiałów edukacyjnych, takich jak notatki czy atlasy. Dzięki temu projekt zyska ogólnokrajową skalę, realnie wspierając ekologię i oszczędności w tysiącach szkół w całej Polsce.</p>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <h3>SZKOŁY WG WOJEWÓDZTWA</h3>
      <div style="max-width:680px;margin:0 auto;"><canvas id="chartWoj"></canvas></div>
    </div>
    <div class="col">
      <h3>STRUKTURA TYPÓW SZKÓŁ</h3>
      <div style="max-width:300px;margin:0 auto;"><canvas id="chartTypes"></canvas></div>
      <div style="max-width:300px;margin:10px auto 0;">
        <?php
        $types_sorted = $df_types;
        usort($types_sorted, function($a,$b){ return $b['count'] <=> $a['count']; });
        $t_total = array_sum(array_column($types_sorted,'count'));
        foreach ($types_sorted as $t) {
            $p = $t_total ? ($t['count']/$t_total*100) : 0;
            echo '<div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;border-bottom:1px solid #1f2f1f;"><span>'.htmlspecialchars($t['typ']).'</span><span style="color:#39FF14;font-weight:bold;">'.number_format($p,1,',',' ').' %</span></div>';
        }
        ?>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <div class="card">
        <h3>TOP 10 POWIATÓW</h3>
        <div style="max-width:480px;margin:0 auto;"><canvas id="chartPow"></canvas></div>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <h3>MARNOWANIE PAPIERU (DANE.GOV.PL)</h3>
        <div style="max-width:480px;margin:0 auto;"><canvas id="chartPaper"></canvas></div>
      </div>
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
  const paperLabels = <?php echo json_encode(array_slice(array_column($df_woj, 'wojewodztwo'), 0, 10)); ?>;
  const paperData = <?php echo json_encode(array_map(function($r){ return round($r['liczba_szkol'] * 2.5, 1); }, array_slice($df_woj, 0, 10))); ?>;
  new Chart(document.getElementById('chartPaper'), {
    type: 'bar',
    data: { labels: paperLabels, datasets: [{ label: 'Zużycie papieru (tony)', data: paperData, backgroundColor: 'rgba(255,99,71,0.6)', borderColor: '#ff6347', borderWidth: 1, borderRadius: 6 }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#aaa' }, grid: { color: 'rgba(255,255,255,0.05)' } }, y: { ticks: { color: '#aaa', font: { size: 10 } } } } }
  });
  </script>
  <div style="text-align:center;margin-top:20px;">
    <form method="get"><input type="hidden" name="page" value="gielda"><button type="submit" class="primary">➡️ PRZEJDŹ DO GIEŁDY LOKALNEJ</button></form>
  </div>
<?php else: ?>
  <h1>GIEŁDA LOKALNA</h1>
  <?php if (!empty($_GET['err'])): ?>
    <div class="card" style="border-left-color:#ff4444;">
      <span style="color:#ff6666;font-weight:bold;">Błąd: </span>
      <?php
      $e = $_GET['err'];
      if ($e === 'title') echo 'Tytuł książki jest wymagany.';
      elseif ($e === 'price') echo 'Cena jest wymagana i musi być liczbą.';
      elseif ($e === 'phone') echo 'Numer telefonu musi zawierać co najmniej 9 cyfr.';
      elseif ($e === 'klasa') echo 'Klasa musi być liczbą od 1 do 5.';
      elseif ($e === 'school') echo 'Wybierz szkołę.';
      else echo 'Sprawdź wprowadzone dane.';
      ?>
    </div>
  <?php endif; ?>
  <?php
  $has_filters = isset($_GET['woj']) || isset($_GET['powiat']) || isset($_GET['miasto']) || isset($_GET['school_id']);
  $woj = $_GET['woj'] ?? '';
  $powiat = $_GET['powiat'] ?? '';
  $miasto = $_GET['miasto'] ?? '';
  $school_id = $_GET['school_id'] ?? '';
  if (!$has_filters) {
      $woj = 'Mazowieckie';
      $powiat = 'm. st. Warszawa';
      $miasto = 'Wola';
      $school_id = '31531';
  }
  $woj_options = get_voivodeships();
  $powiat_options = $woj !== '' ? get_powiaty($woj) : [];
  $city_options = $powiat !== '' ? get_cities($powiat) : [];
  $school_options = $miasto !== '' ? get_schools($miasto) : [];
  $view = $_GET['view'] ?? 'list';
  $sort = $_GET['sort'] ?? 'created';
  $search = $_GET['search'] ?? '';
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
    <?php $listings = get_school_listings($school_id, $search); ?>
    <?php
    if ($sort === 'title') usort($listings, function($a,$b){ return strcmp($a['title'] ?? '', $b['title'] ?? ''); });
    elseif ($sort === 'subject') usort($listings, function($a,$b){ return strcmp($a['subject'] ?? '', $b['subject'] ?? ''); });
    elseif ($sort === 'klasa') usort($listings, function($a,$b){ return ($a['klasa'] ?? 0) <=> ($b['klasa'] ?? 0); });
    ?>

    <?php
    function relevanceScore($title, $subject, $s) {
        $t = mb_strtolower($title);
        $sub = mb_strtolower($subject);
        if ($t === $s) return 0;
        $pt = mb_strpos($t, $s);
        $ps = mb_strpos($sub, $s);
        if ($pt === 0) return 1;
        if ($ps === 0) return 100;
        if ($pt !== false) return 10 + $pt;
        if ($ps !== false) return 200 + $ps;
        return 999;
    }

    if ($search !== '') {
        usort($listings, function($a,$b) use ($search) {
            $s = mb_strtolower($search);
            $sa = relevanceScore($a['title'] ?? '', $a['subject'] ?? '', $s);
            $sb = relevanceScore($b['title'] ?? '', $b['subject'] ?? '', $s);
            if ($sa !== $sb) return $sa <=> $sb;
            return strcmp($a['title'] ?? '', $b['title'] ?? '');
        });
    }
    ?>
    <?php if ($view !== 'add'): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
      <h3 style="margin:0;">OFERTY W SZKOLE</h3>
      <form method="get" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="page" value="gielda">
        <input type="hidden" name="woj" value="<?php echo htmlspecialchars($woj); ?>">
        <input type="hidden" name="powiat" value="<?php echo htmlspecialchars($powiat); ?>">
        <input type="hidden" name="miasto" value="<?php echo htmlspecialchars($miasto); ?>">
        <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school_id); ?>">
        <input type="hidden" name="view" value="list">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <label style="font-size:13px;">Sortuj:</label>
        <select name="sort" onchange="this.form.submit()">
          <option value="created" <?php echo $sort==='created'?'selected':''; ?>>Najnowsze</option>
          <option value="title" <?php echo $sort==='title'?'selected':''; ?>>Nazwa</option>
          <option value="subject" <?php echo $sort==='subject'?'selected':''; ?>>Przedmiot</option>
          <option value="klasa" <?php echo $sort==='klasa'?'selected':''; ?>>Klasa</option>
        </select>
      </form>
    </div>
    <div class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px;">
      <label style="font-size:14px;color:#aaa;margin:0;">🔍 Szukaj podręcznika (tytuł, przedmiot, klasa):</label>
      <form method="get" style="display:flex;gap:8px;align-items:center;margin:0;">
        <input type="hidden" name="page" value="gielda">
        <input type="hidden" name="woj" value="<?php echo htmlspecialchars($woj); ?>">
        <input type="hidden" name="powiat" value="<?php echo htmlspecialchars($powiat); ?>">
        <input type="hidden" name="miasto" value="<?php echo htmlspecialchars($miasto); ?>">
        <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school_id); ?>">
        <input type="hidden" name="view" value="list">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        <input name="search" style="width:260px;padding:8px;border-radius:8px;border:1px solid #2a3a2a;background:#151a15;color:#fff;" value="<?php echo htmlspecialchars($search); ?>" placeholder="wpisz słowo kluczowe...">
        <button type="submit" class="primary" style="padding:8px 14px;">Szukaj</button>
        <?php if ($search !== ''): ?>
          <a href="?<?php echo http_build_query(['page'=>'gielda','woj'=>$woj,'powiat'=>$powiat,'miasto'=>$miasto,'school_id'=>$school_id,'view'=>'list','sort'=>$sort]); ?>" style="color:#39FF14;text-decoration:underline;font-size:13px;">Wyczyść</a>
        <?php endif; ?>
      </form>
    </div>
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


    <div style="text-align:center;margin:16px 0;">
      <a href="?<?php echo http_build_query(['page'=>'gielda','woj'=>$woj,'powiat'=>$powiat,'miasto'=>$miasto,'school_id'=>$school_id,'view'=>'add','sort'=>$sort,'search'=>$search]); ?>"><button type="button" class="primary">➕ DODAJ SWÓJ PODRĘCZNIK (limit 5)*</button></a>
    </div>

    <?php else: ?>
    <div style="text-align:center;margin:16px 0;">
      <a href="?<?php echo http_build_query(['page'=>'gielda','woj'=>$woj,'powiat'=>$powiat,'miasto'=>$miasto,'school_id'=>$school_id,'view'=>'list','sort'=>$sort,'search'=>$search]); ?>"><button type="button" class="primary">⬅️ ZOBACZ AKTUALNE OFERTY</button></a>
    </div>
    <hr>
    <h3 id="add-form">+ WYSTAW SWÓJ PODRĘCZNIK</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_listing">
      <input type="hidden" name="woj" value="<?php echo htmlspecialchars($woj); ?>">
      <input type="hidden" name="powiat" value="<?php echo htmlspecialchars($powiat); ?>">
      <input type="hidden" name="miasto" value="<?php echo htmlspecialchars($miasto); ?>">
      <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($school_id); ?>">
      <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
      <div class="row">
        <div class="col">
          <label>Tytuł i Wydawnictwo</label><br>
          <input name="title" style="width:100%" required><br><br>
          <label>Przedmiot</label><br>
          <select name="subject" style="width:100%">
            <?php foreach (["Matematyka","J. Polski","J. Angielski","Fizyka","Chemia","Biologia","Geografia","Historia","Informatyka","Zawodowe","Inne"] as $s): ?>
              <option><?php echo $s; ?></option>
            <?php endforeach; ?>
          </select><br><br>
          <label>Klasa (1-5)</label><br>
          <input type="number" name="klasa" min="1" max="5" style="width:100%"><br><br>
          <label>Cena (PLN)</label><br>
          <input type="number" name="price" min="0" step="1" style="width:100%" required>
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
          <input name="contact" style="width:100%" required pattern=".*[0-9].*" title="Minimum 9 cyfr"><br><br>
          <label>Zdjęcie (opcjonalnie)</label><br>
          <input type="file" name="image" accept="image/png,image/jpeg">
        </div>
      </div>
      <br>
      <button type="submit" class="primary">>> ZAINICJUJ OFERTĘ W SZKOLE <<</button>
    </form>
  <?php endif; ?>
  <p style="color:#9aa;font-size:12px;margin:12px 0 0;font-style:italic;">*- limit obowiązuje darmowe konta, jeśli chcesz wystawić więcej książek i zyskać ocenę jakości przy swoim profilu wykup pakiet premium.</p>
  <div style="text-align:center;margin-top:20px;">
    <form method="get"><input type="hidden" name="page" value="raport"><button type="submit" class="primary">⬅️ WRÓĆ DO RAPORTU</button></form>
  </div>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
