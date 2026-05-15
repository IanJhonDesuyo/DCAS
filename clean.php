<?php
session_start();

/*
  Guest/demo mode since login was removed.
*/
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Guest';
    $_SESSION['user_name'] = 'Guest';
    $_SESSION['role'] = 'guest';
}

require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Enforce workflow: must upload and profile first
check_workflow_step($conn, 'clean');

// Get file ID from session
$id = (int)$_SESSION['file_id'];
$file = get_file_or_redirect($conn, $id);

$src = dcas_uploads_dir() . '/' . $file['stored_name'];
$cleanedName = $file['cleaned_name'] ?: ('clean_' . $file['stored_name']);
$cleanedPath = dcas_uploads_dir() . '/' . $cleanedName;

[$headers, $rows] = read_csv($src);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start from original each time -> deterministic, easy to defend
    $work = $rows;
    $logs = [];

    // 1) Trim spaces / capitalize
    if (!empty($_POST['trim'])) {
        $changed = 0;

        foreach ($work as &$r) {
            foreach ($r as &$v) {
                $orig = $v;
                $v = trim((string)$v);

                if ($orig !== $v) {
                    $changed++;
                }
            }
        }

        $logs[] = ['standardize_text', "Trimmed leading/trailing whitespace in all cells. Cells changed: $changed.", $changed];
    }

    if (!empty($_POST['title_case'])) {
        $changed = 0;

        foreach ($work as &$r) {
            foreach ($r as &$v) {
                if (is_string($v) && !is_numeric($v) && $v !== '') {
                    $new = mb_convert_case(mb_strtolower($v), MB_CASE_TITLE, 'UTF-8');

                    if ($new !== $v) {
                        $v = $new;
                        $changed++;
                    }
                }
            }
        }

        $logs[] = ['standardize_text', "Applied Title Case to text cells for consistency. Changed: $changed.", $changed];
    }

    // 2) Standardize date formats
    if (!empty($_POST['standardize_dates'])) {
        $changed = 0;

        for ($c = 0; $c < count($headers); $c++) {
            $col = array_column($work, $c);
            $type = detect_type($col);

            if ($type === 'date') {
                foreach ($work as &$r) {
                    if (!is_missing($r[$c])) {
                        $ts = strtotime($r[$c]);

                        if ($ts !== false) {
                            $new = date('Y-m-d', $ts);

                            if ($new !== $r[$c]) {
                                $r[$c] = $new;
                                $changed++;
                            }
                        }
                    }
                }
            }
        }

        $logs[] = ['standardize_dates', "Converted detected date columns to ISO format YYYY-MM-DD. Cells changed: $changed.", $changed];
    }

    // 3) Convert types
    if (!empty($_POST['convert_types'])) {
        $changed = 0;

        for ($c = 0; $c < count($headers); $c++) {
            $col = array_column($work, $c);
            $type = detect_type($col);

            if (in_array($type, ['integer', 'float'])) {
                foreach ($work as &$r) {
                    if (!is_missing($r[$c]) && is_numeric(str_replace(',', '', $r[$c]))) {
                        $new = (string)(float)str_replace(',', '', $r[$c]);

                        if ($type === 'integer') {
                            $new = (string)(int)$new;
                        }

                        if ($new !== $r[$c]) {
                            $r[$c] = $new;
                            $changed++;
                        }
                    }
                }
            }
        }

        $logs[] = ['convert_types', "Normalized numeric columns (removed commas, cast to integer/float). Cells changed: $changed.", $changed];
    }

    // 4) Handle missing values
    $missing_strategy = $_POST['missing'] ?? 'none';

    if ($missing_strategy === 'remove_rows') {
        $before = count($work);

        $work = array_values(array_filter($work, function ($r) {
            foreach ($r as $v) {
                if (is_missing($v)) {
                    return false;
                }
            }

            return true;
        }));

        $removed = $before - count($work);
        $logs[] = ['missing_remove_rows', "Removed rows containing any missing value. Rows removed: $removed.", $removed];
    } elseif ($missing_strategy === 'fill') {
        $changed = 0;

        for ($c = 0; $c < count($headers); $c++) {
            $col = array_column($work, $c);
            $type = detect_type($col);
            $fill = '';

            if (in_array($type, ['integer', 'float'])) {
                $nums = array_map('floatval', array_filter($col, fn($v) => !is_missing($v) && is_numeric($v)));
                $fill = $nums ? (string)round(array_sum($nums) / count($nums), 2) : '0';
            } else {
                $clean = array_filter($col, fn($v) => !is_missing($v));
                $freq = array_count_values(array_map('strval', $clean));
                arsort($freq);
                $fill = $freq ? (string)array_key_first($freq) : 'Unknown';
            }

            foreach ($work as &$r) {
                if (is_missing($r[$c])) {
                    $r[$c] = $fill;
                    $changed++;
                }
            }
        }

        $logs[] = ['missing_fill', "Filled missing values: numeric→column average, text→most frequent. Cells filled: $changed.", $changed];
    }

    // 5) Remove duplicates
    if (!empty($_POST['remove_duplicates'])) {
        $before = count($work);
        $seen = [];
        $out = [];

        foreach ($work as $r) {
            $k = implode('||', $r);

            if (!isset($seen[$k])) {
                $seen[$k] = 1;
                $out[] = $r;
            }
        }

        $work = $out;
        $removed = $before - count($work);

        $logs[] = ['remove_duplicates', "Removed exact duplicate rows. Rows removed: $removed.", $removed];
    }

    // 6) Filter invalid
    if (!empty($_POST['filter_invalid'])) {
        $before = count($work);

        $work = array_values(array_filter($work, function ($r) use ($headers) {
            for ($i = 0; $i < count($headers); $i++) {
                $name = strtolower($headers[$i]);
                $v = $r[$i];

                if (is_missing($v)) {
                    continue;
                }

                if (preg_match('/(qty|quantity|price|amount|total|sales)/', $name) && is_numeric($v) && $v < 0) {
                    return false;
                }

                if (preg_match('/date/', $name) && strtotime($v) === false) {
                    return false;
                }
            }

            return true;
        }));

        $removed = $before - count($work);
        $logs[] = ['filter_invalid', "Filtered invalid rows (negative numeric in qty/price/amount, unparsable dates). Rows removed: $removed.", $removed];
    }

    // Save cleaned dataset
    write_csv($cleanedPath, $headers, $work);

    $stmt = $conn->prepare('UPDATE uploaded_files SET cleaned_name = ? WHERE id = ?');
    $stmt->bind_param('si', $cleanedName, $id);
    $stmt->execute();

    // Log
    $user_id = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare('INSERT INTO cleaning_logs (file_id, user_id, action, details, affected_rows) VALUES (?, ?, ?, ?, ?)');

    foreach ($logs as $l) {
        $stmt->bind_param('iissi', $id, $user_id, $l[0], $l[1], $l[2]);
        $stmt->execute();
    }

    log_action($conn, $id, 'clean', count($logs) . ' cleaning operations applied');

    $msg = 'Cleaning applied. ' . count($logs) . ' operations logged. Cleaned dataset has ' . count($work) . ' rows.';
}

// Recompute logs to show
$user_id = (int)$_SESSION['user_id'];
$logs = $conn->query("SELECT * FROM cleaning_logs WHERE file_id = $id AND user_id = $user_id ORDER BY id DESC LIMIT 20");

require_once 'includes/header.php';
?>

<style>
  .clean-page{
    position:relative;
    isolation:isolate;
  }

  .clean-page::before{
    content:"";
    position:absolute;
    inset:-120px -40px auto -40px;
    height:520px;
    z-index:-1;
    background:
      radial-gradient(600px 300px at 15% 20%, rgba(16,185,129,.16), transparent 60%),
      radial-gradient(500px 260px at 85% 10%, rgba(20,184,166,.16), transparent 60%),
      radial-gradient(700px 320px at 50% 90%, rgba(110,231,183,.14), transparent 60%);
    filter:blur(2px);
  }

  .clean-hero{
    position:relative;
    border-radius:30px;
    padding:3rem;
    background:linear-gradient(135deg, rgba(255,255,255,.90), rgba(255,255,255,.66));
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 30px 80px -45px rgba(4,120,87,.45), 0 8px 24px -14px rgba(15,23,42,.08);
    backdrop-filter:blur(10px);
    overflow:hidden;
  }

  .clean-hero::after{
    content:"";
    position:absolute;
    right:-120px;
    top:-120px;
    width:340px;
    height:340px;
    border-radius:50%;
    background:conic-gradient(from 120deg, #047857, #6ee7b7, #14b8a6, #047857);
    filter:blur(60px);
    opacity:.35;
    z-index:0;
  }

  .clean-hero > *{
    position:relative;
    z-index:1;
  }

  .eyebrow{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    padding:.42rem .85rem;
    border-radius:999px;
    background:rgba(16,185,129,.10);
    color:#047857;
    font-weight:900;
    font-size:.78rem;
    letter-spacing:.06em;
    text-transform:uppercase;
    border:1px solid rgba(16,185,129,.18);
  }

  .clean-title{
    margin:.95rem 0 .55rem;
    font-size:clamp(2rem, 4vw, 3.1rem);
    line-height:1.05;
    font-weight:950;
    letter-spacing:-.045em;
    background:linear-gradient(120deg, #052e2b 0%, #047857 48%, #10b981 78%, #14b8a6 100%);
    -webkit-background-clip:text;
    background-clip:text;
    color:transparent;
  }

  .clean-subtitle{
    color:#475569;
    font-size:1.05rem;
    max-width:760px;
    margin:0;
  }

  .file-chip-main{
    display:inline-flex;
    align-items:center;
    gap:.6rem;
    padding:.75rem .95rem;
    border-radius:16px;
    background:#fff;
    border:1px solid rgba(15,23,42,.08);
    box-shadow:0 10px 28px -20px rgba(15,23,42,.25);
    color:#334155;
    font-weight:800;
    max-width:100%;
  }

  .file-chip-main span{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    max-width:340px;
  }

  .glass-card,
  .history-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:26px;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(10px);
    box-shadow:0 22px 60px -38px rgba(4,120,87,.38);
    overflow:hidden;
  }

  .card-head{
    padding:1.25rem 1.45rem;
    border-bottom:1px solid rgba(15,23,42,.06);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .card-title{
    margin:0;
    font-weight:950;
    color:#0f172a;
    display:flex;
    align-items:center;
    gap:.55rem;
  }

  .card-subtitle{
    margin:.35rem 0 0;
    color:#64748b;
    font-size:.92rem;
  }

  .card-body-custom{
    padding:1.55rem;
  }

  .operation-card{
    position:relative;
    border:1px solid rgba(15,23,42,.07);
    border-radius:20px;
    padding:1rem 1rem 1rem 1.15rem;
    background:linear-gradient(180deg, #fff, #fbfffd);
    margin-bottom:1rem;
    overflow:hidden;
    transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease;
  }

  .operation-card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    bottom:0;
    width:5px;
    background:var(--accent, #10b981);
  }

  .operation-card:hover{
    transform:translateY(-2px);
    border-color:rgba(16,185,129,.20);
    box-shadow:0 16px 40px -34px rgba(4,120,87,.70);
  }

  .operation-title{
    font-weight:900;
    color:#0f172a;
  }

  .operation-desc{
    color:#64748b;
    font-size:.88rem;
    margin:.25rem 0 0;
  }

  .form-check-input{
    cursor:pointer;
  }

  .form-check-input:checked{
    background-color:#10b981;
    border-color:#10b981;
  }

  .form-check-input:focus{
    border-color:#10b981;
    box-shadow:0 0 0 .25rem rgba(16,185,129,.16);
  }

  .missing-box{
    border:1px solid rgba(15,23,42,.07);
    border-radius:20px;
    padding:1rem 1rem 1rem 1.15rem;
    background:linear-gradient(180deg, #fff, #fbfffd);
    margin-bottom:1rem;
    position:relative;
    overflow:hidden;
  }

  .missing-box::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    bottom:0;
    width:5px;
    background:#14b8a6;
  }

  .missing-title{
    font-weight:900;
    color:#0f172a;
    margin-bottom:.7rem;
  }

  .btn-hero{
    min-height:52px;
    border-radius:15px;
    background:linear-gradient(135deg, #047857, #10b981);
    border:none;
    color:#fff;
    font-weight:900;
    box-shadow:0 14px 34px -18px rgba(4,120,87,.70);
    transition:transform .15s ease, filter .15s ease;
  }

  .btn-hero:hover{
    transform:translateY(-2px);
    filter:brightness(1.04);
    color:#fff;
  }

  .history-item{
    border:1px solid rgba(16,185,129,.12);
    border-radius:18px;
    padding:1rem;
    margin-bottom:.85rem;
    background:linear-gradient(135deg, rgba(16,185,129,.055), rgba(20,184,166,.045));
  }

  .history-action{
    color:#047857;
    font-weight:900;
  }

  .empty-history{
    text-align:center;
    padding:2.5rem 1rem;
    color:#64748b;
  }

  .empty-history i{
    font-size:2.8rem;
    color:#94a3b8;
    display:block;
    margin-bottom:1rem;
  }

  .success-alert{
    border:1px solid rgba(16,185,129,.20);
    border-radius:20px;
    background:linear-gradient(135deg, rgba(16,185,129,.10), rgba(255,255,255,.95));
    color:#065f46;
    box-shadow:0 18px 45px -36px rgba(4,120,87,.45);
  }

  @media (max-width: 991px){
    .clean-hero{
      padding:2rem;
    }

    .card-body-custom{
      padding:1.2rem;
    }
  }
</style>

<div class="clean-page">

  <div class="clean-hero mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <span class="eyebrow">
          <i class="bi bi-magic"></i>
          Step 3 · Data Cleaning
        </span>

        <h1 class="clean-title">Clean Your Dataset</h1>

        <p class="clean-subtitle">
          Select the cleaning operations you want to apply. The system will create a cleaned version of your dataset and record every operation for tracking.
        </p>
      </div>

      <div class="file-chip-main">
        <i class="bi bi-file-earmark-spreadsheet text-success"></i>
        <span><?= htmlspecialchars($file['original_name']) ?></span>
      </div>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="success-alert auto-dismiss d-flex align-items-start gap-2 p-4 mb-4">
      <i class="bi bi-check-circle-fill mt-1"></i>
      <div>
        <strong>Cleaning Completed</strong>
        <div><?= htmlspecialchars($msg) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" class="row g-4">
    <div class="col-lg-7">
      <div class="glass-card">
        <div class="card-head">
          <h5 class="card-title">
            <i class="bi bi-sliders text-success"></i>
            Cleaning Operations
          </h5>
          <p class="card-subtitle">
            Choose which corrections should be applied to the uploaded dataset.
          </p>
        </div>

        <div class="card-body-custom">

          <div class="operation-card" style="--accent:#047857;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remove_duplicates" id="rd" checked>
              <label class="form-check-label operation-title" for="rd">
                <i class="bi bi-diagram-2 me-2 text-success"></i>
                Remove Duplicate Rows
              </label>
              <p class="operation-desc">Eliminates exact duplicate records so counts and metrics are accurate.</p>
            </div>
          </div>

          <div class="missing-box">
            <div class="missing-title">
              <i class="bi bi-funnel me-2 text-success"></i>
              Missing Values Strategy
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="missing" id="m1" value="none" checked>
              <label class="form-check-label" for="m1">Keep empty cells as-is</label>
            </div>

            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="missing" id="m2" value="remove_rows">
              <label class="form-check-label" for="m2">Remove rows with any missing values</label>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="radio" name="missing" id="m3" value="fill">
              <label class="form-check-label" for="m3">Auto-fill numbers with average and text with most frequent value</label>
            </div>
          </div>

          <div class="operation-card" style="--accent:#10b981;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="convert_types" id="ct" checked>
              <label class="form-check-label operation-title" for="ct">
                <i class="bi bi-diagram-3 me-2 text-success"></i>
                Convert Data Types
              </label>
              <p class="operation-desc">Normalize numeric columns by removing commas and converting values to proper numbers.</p>
            </div>
          </div>

          <div class="operation-card" style="--accent:#64748b;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="trim" id="tr" checked>
              <label class="form-check-label operation-title" for="tr">
                <i class="bi bi-scissors me-2 text-secondary"></i>
                Trim Whitespace
              </label>
              <p class="operation-desc">Remove extra spaces before and after text values.</p>
            </div>
          </div>

          <div class="operation-card" style="--accent:#f59e0b;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="title_case" id="tc">
              <label class="form-check-label operation-title" for="tc">
                <i class="bi bi-type me-2 text-warning"></i>
                Standardize Capitalization
              </label>
              <p class="operation-desc">Convert text values to Title Case for consistent formatting.</p>
            </div>
          </div>

          <div class="operation-card" style="--accent:#14b8a6;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="standardize_dates" id="sd" checked>
              <label class="form-check-label operation-title" for="sd">
                <i class="bi bi-calendar me-2 text-success"></i>
                Standardize Date Formats
              </label>
              <p class="operation-desc">Convert detected dates into YYYY-MM-DD format.</p>
            </div>
          </div>

          <div class="operation-card" style="--accent:#dc3545;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="filter_invalid" id="fi" checked>
              <label class="form-check-label operation-title" for="fi">
                <i class="bi bi-exclamation-circle me-2 text-danger"></i>
                Filter Invalid Rows
              </label>
              <p class="operation-desc">Remove records with negative amounts or unreadable dates.</p>
            </div>
          </div>

          <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-hero btn-lg">
              <i class="bi bi-magic me-2"></i>
              Apply Selected Cleaning
            </button>
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="history-card">
        <div class="card-head">
          <h5 class="card-title">
            <i class="bi bi-clock-history text-success"></i>
            Operation History
          </h5>
          <p class="card-subtitle">
            Recent cleaning actions applied to this dataset.
          </p>
        </div>

        <div class="card-body-custom">
          <?php if ($logs->num_rows === 0): ?>
            <div class="empty-history">
              <i class="bi bi-info-circle"></i>
              <p class="mb-0">No operations yet. Apply cleaning to track changes.</p>
            </div>
          <?php else: ?>
            <?php while ($l = $logs->fetch_assoc()): ?>
              <div class="history-item">
                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                  <div class="history-action">
                    <i class="bi bi-check-circle me-1"></i>
                    <?= htmlspecialchars($l['action']) ?>
                  </div>

                  <span class="badge rounded-pill text-bg-primary">
                    <?= (int)$l['affected_rows'] ?> rows
                  </span>
                </div>

                <div class="small text-muted">
                  <?= htmlspecialchars($l['details']) ?>
                </div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>

      <a href="analyze.php" class="btn btn-outline-primary btn-lg w-100 mt-3" style="border-radius:15px; font-weight:800;">
        <i class="bi bi-bar-chart me-2"></i>
        Continue to Analysis
      </a>
    </div>
  </form>

</div>

<?php require_once 'includes/footer.php'; ?>