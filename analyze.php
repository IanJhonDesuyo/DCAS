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

// Enforce workflow: must upload, profile, and clean first
check_workflow_step($conn, 'analyze');

// Get file ID from session
$id = (int)$_SESSION['file_id'];
$file = get_file_or_redirect($conn, $id);

$src = dcas_uploads_dir() . '/' . $file['stored_name'];
$cleanedPath = $file['cleaned_name'] ? dcas_uploads_dir() . '/' . $file['cleaned_name'] : null;

[$h1, $rows1] = read_csv($src);

if ($cleanedPath && file_exists($cleanedPath)) {
    [$h2, $rows2] = read_csv($cleanedPath);
} else {
    $h2 = $h1;
    $rows2 = $rows1;
}

// Stats
$dups_before = count_duplicate_rows($rows1);
$dups_after = count_duplicate_rows($rows2);

$miss_before = 0;
$miss_after = 0;

foreach ($rows1 as $r) {
    foreach ($r as $v) {
        if (is_missing($v)) {
            $miss_before++;
        }
    }
}

foreach ($rows2 as $r) {
    foreach ($r as $v) {
        if (is_missing($v)) {
            $miss_after++;
        }
    }
}

// Index original rows by key for diff
$orig_keys = [];

foreach ($rows1 as $r) {
    $orig_keys[implode('||', $r)] = true;
}

$cleaned_keys = [];

foreach ($rows2 as $r) {
    $cleaned_keys[implode('||', $r)] = true;
}

$removed = 0;

foreach ($orig_keys as $k => $_) {
    if (!isset($cleaned_keys[$k])) {
        $removed++;
    }
}

// Generate insights
$user_id = (int)$_SESSION['user_id'];

$conn->query("DELETE FROM insights WHERE file_id = $id AND user_id = $user_id");

$insights = [];
$profile = profile_dataset($h2, $rows2);

$insights[] = "Dataset has " . count($rows2) . " rows and " . count($h2) . " columns after cleaning.";

foreach ($profile as $p) {
    if ($p['most_frequent'] !== null && $p['type'] === 'string') {
        $insights[] = "The most common value in '{$p['name']}' is '{$p['most_frequent']}'.";
    }

    if (in_array($p['type'], ['integer', 'float']) && $p['avg'] !== null) {
        $insights[] = "Column '{$p['name']}' has an average of {$p['avg']} (min {$p['min']}, max {$p['max']}).";
    }

    if ($p['type'] === 'date' && $p['min'] && $p['max']) {
        $insights[] = "'{$p['name']}' spans from {$p['min']} to {$p['max']}.";
    }
}

$insights[] = "Cleaning resolved $dups_before duplicate row(s) and $miss_before missing cell(s); $removed row(s) removed in total.";

$stmt = $conn->prepare('INSERT INTO insights (file_id, user_id, insight_text) VALUES (?, ?, ?)');

foreach ($insights as $t) {
    $stmt->bind_param('iis', $id, $user_id, $t);
    $stmt->execute();
}

require_once 'includes/header.php';
?>

<style>
  .analyze-page{
    position:relative;
    isolation:isolate;
  }

  .analyze-page::before{
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

  .analyze-hero{
    position:relative;
    border-radius:30px;
    padding:3rem;
    background:linear-gradient(135deg, rgba(255,255,255,.90), rgba(255,255,255,.66));
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 30px 80px -45px rgba(4,120,87,.45), 0 8px 24px -14px rgba(15,23,42,.08);
    backdrop-filter:blur(10px);
    overflow:hidden;
  }

  .analyze-hero::after{
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

  .analyze-hero > *{
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

  .analyze-title{
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

  .analyze-subtitle{
    color:#475569;
    font-size:1.05rem;
    max-width:780px;
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

  .stat-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:22px;
    background:linear-gradient(180deg, #fff, #fbfffd);
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    position:relative;
    overflow:hidden;
    box-shadow:0 18px 50px -36px rgba(4,120,87,.28);
    height:100%;
  }

  .stat-card::before{
    content:"";
    position:absolute;
    left:0;
    top:0;
    bottom:0;
    width:5px;
    background:var(--accent, #10b981);
  }

  .stat-card:hover{
    transform:translateY(-4px);
    box-shadow:0 24px 60px -36px rgba(4,120,87,.42);
    border-color:rgba(16,185,129,.22);
  }

  .stat-icon{
    width:54px;
    height:54px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--icon-bg, rgba(16,185,129,.10));
    flex-shrink:0;
  }

  .stat-icon i{
    font-size:1.5rem;
    color:var(--accent, #10b981);
  }

  .stat-label{
    font-size:.76rem;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#64748b;
  }

  .stat-value{
    font-size:2.1rem;
    font-weight:950;
    color:var(--accent, #10b981);
    line-height:1;
  }

  .stat-note{
    margin-top:.85rem;
    color:#64748b;
    font-size:.88rem;
  }

  .glass-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:26px;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(10px);
    box-shadow:0 22px 60px -38px rgba(4,120,87,.38);
    overflow:hidden;
  }

  .nav-tabs{
    border-bottom:1px solid rgba(15,23,42,.08);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .nav-tabs .nav-link{
    border:none;
    color:#64748b;
    font-weight:900;
    padding:1rem 1.2rem;
    border-radius:14px 14px 0 0;
  }

  .nav-tabs .nav-link.active{
    color:#047857;
    background:rgba(16,185,129,.09);
  }

  .tab-pane{
    color:#0f172a;
  }

  .table-shell{
    border:1px solid rgba(15,23,42,.08);
    border-radius:18px;
    overflow:hidden;
    background:#fff;
  }

  .table-preview{
    margin-bottom:0;
    font-size:.86rem;
  }

  .table-preview thead th{
    background:linear-gradient(135deg, #f8fafc, #ecfdf5);
    color:#334155;
    font-size:.75rem;
    text-transform:uppercase;
    letter-spacing:.06em;
    white-space:nowrap;
    font-weight:900;
  }

  .table-preview tbody td{
    color:#334155;
    vertical-align:middle;
    white-space:nowrap;
  }

  .table-preview tbody tr:hover{
    background:rgba(16,185,129,.045);
  }

  .diff-removed{
    background:rgba(220,53,69,.08) !important;
  }

  .diff-changed{
    background:rgba(16,185,129,.08) !important;
  }

  .insight-panel{
    border:1px solid rgba(16,185,129,.18);
    border-radius:26px;
    background:linear-gradient(135deg, rgba(236,253,245,.90), rgba(255,255,255,.95));
    box-shadow:0 22px 60px -40px rgba(4,120,87,.34);
    overflow:hidden;
  }

  .insight-head{
    padding:1.25rem 1.45rem;
    border-bottom:1px solid rgba(16,185,129,.14);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .insight-title{
    margin:0;
    font-weight:950;
    color:#047857;
    display:flex;
    align-items:center;
    gap:.55rem;
  }

  .insight-list{
    list-style:none;
    margin:0;
    padding:1.45rem;
  }

  .insight-list li{
    display:flex;
    align-items:flex-start;
    gap:.75rem;
    padding:.8rem 0;
    border-bottom:1px solid rgba(16,185,129,.12);
    color:#475569;
    line-height:1.55;
  }

  .insight-list li:last-child{
    border-bottom:none;
  }

  .insight-icon{
    width:32px;
    height:32px;
    border-radius:11px;
    background:rgba(16,185,129,.10);
    color:#047857;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
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
    color:#fff;
    filter:brightness(1.04);
  }

  @media (max-width: 991px){
    .analyze-hero{
      padding:2rem;
    }

    .nav-tabs .nav-link{
      padding:.9rem .8rem;
      font-size:.9rem;
    }
  }
</style>

<div class="analyze-page">

  <div class="analyze-hero mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <span class="eyebrow">
          <i class="bi bi-lightbulb"></i>
          Step 4 · Comparison & Insights
        </span>

        <h1 class="analyze-title">
          Compare & Understand Results
        </h1>

        <p class="analyze-subtitle">
          Review how the dataset changed after cleaning. This page compares original and cleaned records, highlights fixes, and summarizes key insights.
        </p>
      </div>

      <div class="file-chip-main">
        <i class="bi bi-file-earmark-spreadsheet text-success"></i>
        <span><?= htmlspecialchars($file['original_name']) ?></span>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#047857; --icon-bg:rgba(16,185,129,.10);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-file-text"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Original</div>
            <div class="stat-value"><?= number_format((int)count($rows1)) ?></div>
          </div>
        </div>

        <div class="stat-note">Rows before cleaning.</div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#10b981; --icon-bg:rgba(16,185,129,.10);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-check-circle"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Cleaned</div>
            <div class="stat-value"><?= number_format((int)count($rows2)) ?></div>
          </div>
        </div>

        <div class="stat-note">Rows after cleaning.</div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#f59e0b; --icon-bg:rgba(245,158,11,.12);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-diagram-2"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Duplicates Fixed</div>
            <div class="stat-value"><?= number_format((int)max(0, $dups_before - $dups_after)) ?></div>
          </div>
        </div>

        <div class="stat-note">Duplicate rows reduced.</div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="stat-card p-4" style="--accent:#14b8a6; --icon-bg:rgba(20,184,166,.11);">
        <div class="d-flex align-items-center">
          <div class="stat-icon">
            <i class="bi bi-tools"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Missing Fixed</div>
            <div class="stat-value"><?= number_format((int)max(0, $miss_before - $miss_after)) ?></div>
          </div>
        </div>

        <div class="stat-note">Missing cells reduced.</div>
      </div>
    </div>
  </div>

  <div class="glass-card mb-4">
    <ul class="nav nav-tabs px-4 pt-3" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-orig" type="button">
          <i class="bi bi-file-text me-2"></i>
          Original Data
        </button>
      </li>

      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-clean" type="button">
          <i class="bi bi-check-circle me-2"></i>
          Cleaned Data
        </button>
      </li>

      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-side" type="button">
          <i class="bi bi-columns-gap me-2"></i>
          Side-by-Side
        </button>
      </li>
    </ul>

    <div class="tab-content p-4">
      <div class="tab-pane fade show active" id="tab-orig">
        <div class="table-shell scroll-x">
          <table class="table table-sm table-bordered table-preview">
            <thead>
              <tr>
                <?php foreach ($h1 as $h): ?>
                  <th><?= htmlspecialchars($h) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>

            <tbody>
              <?php foreach (array_slice($rows1, 0, 30) as $r): ?>
                <tr class="<?= !isset($cleaned_keys[implode('||', $r)]) ? 'diff-removed' : '' ?>">
                  <?php foreach ($r as $c): ?>
                    <td><?= htmlspecialchars((string)$c) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-clean">
        <div class="table-shell scroll-x">
          <table class="table table-sm table-bordered table-preview">
            <thead>
              <tr>
                <?php foreach ($h2 as $h): ?>
                  <th><?= htmlspecialchars($h) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>

            <tbody>
              <?php foreach (array_slice($rows2, 0, 30) as $r): ?>
                <tr class="<?= !isset($orig_keys[implode('||', $r)]) ? 'diff-changed' : '' ?>">
                  <?php foreach ($r as $c): ?>
                    <td><?= htmlspecialchars((string)$c) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-side">
        <div class="row g-3">
          <div class="col-md-6">
            <h6 class="fw-bold mb-2">
              <i class="bi bi-file-text me-2"></i>
              Original
            </h6>

            <div class="table-shell scroll-x">
              <table class="table table-sm table-bordered table-preview">
                <thead>
                  <tr>
                    <?php foreach ($h1 as $h): ?>
                      <th><?= htmlspecialchars($h) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>

                <tbody>
                  <?php foreach (array_slice($rows1, 0, 15) as $r): ?>
                    <tr>
                      <?php foreach ($r as $c): ?>
                        <td><?= htmlspecialchars((string)$c) ?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fw-bold mb-2">
              <i class="bi bi-check-circle me-2 text-success"></i>
              Cleaned
            </h6>

            <div class="table-shell scroll-x">
              <table class="table table-sm table-bordered table-preview">
                <thead>
                  <tr>
                    <?php foreach ($h2 as $h): ?>
                      <th><?= htmlspecialchars($h) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>

                <tbody>
                  <?php foreach (array_slice($rows2, 0, 15) as $r): ?>
                    <tr>
                      <?php foreach ($r as $c): ?>
                        <td><?= htmlspecialchars((string)$c) ?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <div class="insight-panel mb-4">
    <div class="insight-head">
      <h5 class="insight-title">
        <i class="bi bi-lightbulb-fill"></i>
        Auto-Generated Insights
      </h5>
    </div>

    <ul class="insight-list">
      <?php foreach ($insights as $t): ?>
        <li>
          <span class="insight-icon">
            <i class="bi bi-stars"></i>
          </span>

          <span><?= htmlspecialchars($t) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <a href="dashboard.php" class="btn btn-hero btn-lg mb-4">
    <i class="bi bi-bar-chart-line me-2"></i>
    Open Dashboard & Visualize
  </a>

</div>

<?php require_once 'includes/footer.php'; ?>