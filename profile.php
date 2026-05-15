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

$user_id = (int)$_SESSION['user_id'];

$file = null;
$headers = [];
$rows = [];
$profile = [];
$dups = 0;
$total_missing = 0;
$alert_message = "";

/*
  Workflow logic:
  The user must upload a dataset first before opening the Profile step.
  If no file is selected/uploaded, profile.php will still open,
  but it will show a caution alert.
*/

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $_SESSION['file_id'] = (int)$_GET['id'];
}

if (!isset($_SESSION['file_id']) || empty($_SESSION['file_id'])) {
    $alert_message = "Please upload a dataset first before proceeding to the Profiling step.";
} else {
    $id = (int)$_SESSION['file_id'];

    $stmt = $conn->prepare("SELECT * FROM uploaded_files WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();

    if (!$file) {
        unset($_SESSION['file_id']);
        $alert_message = "The selected dataset could not be found. Please upload or select a dataset first.";
    } else {
        $path = dcas_uploads_dir() . '/' . $file['stored_name'];

        if (!file_exists($path)) {
            $alert_message = "The uploaded file record exists, but the actual file is missing from the uploads folder.";
            $file = null;
        } else {
            [$headers, $rows] = read_csv($path);
            $profile = profile_dataset($headers, $rows);
            $dups = count_duplicate_rows($rows);
            $total_missing = array_sum(array_column($profile, 'missing'));

            // Refresh metadata for this user's selected file
            $stmt = $conn->prepare("DELETE FROM dataset_metadata WHERE file_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("
                INSERT INTO dataset_metadata 
                (file_id, user_id, column_name, data_type, missing_count, unique_count, most_frequent, min_value, max_value, avg_value) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($profile as $p) {
                $mf = (string)$p['most_frequent'];
                $mn = (string)$p['min'];
                $mx = (string)$p['max'];
                $av = $p['avg'] !== null ? (string)$p['avg'] : null;

                $stmt->bind_param(
                    'iissiissss',
                    $id,
                    $user_id,
                    $p['name'],
                    $p['type'],
                    $p['missing'],
                    $p['unique'],
                    $mf,
                    $mn,
                    $mx,
                    $av
                );

                $stmt->execute();
            }
        }
    }
}

require_once 'includes/header.php';
?>

<style>
  .profile-page{
    position:relative;
    isolation:isolate;
  }

  .profile-page::before{
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

  .profile-hero{
    position:relative;
    border-radius:30px;
    padding:3rem;
    background:linear-gradient(135deg, rgba(255,255,255,.90), rgba(255,255,255,.66));
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 30px 80px -45px rgba(4,120,87,.45), 0 8px 24px -14px rgba(15,23,42,.08);
    backdrop-filter:blur(10px);
    overflow:hidden;
  }

  .profile-hero::after{
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

  .profile-hero > *{
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

  .profile-title{
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

  .profile-subtitle{
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

  .glass-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:26px;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(10px);
    box-shadow:0 22px 60px -38px rgba(4,120,87,.38);
    overflow:hidden;
  }

  .card-head-custom{
    padding:1.25rem 1.45rem;
    border-bottom:1px solid rgba(15,23,42,.06);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .card-title-custom{
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
    padding:1.45rem;
  }

  .table-shell{
    border:1px solid rgba(15,23,42,.08);
    border-radius:18px;
    overflow:hidden;
    background:#fff;
  }

  .profile-table{
    margin-bottom:0;
    font-size:.87rem;
  }

  .profile-table thead th{
    background:linear-gradient(135deg, #f8fafc, #ecfdf5);
    color:#334155;
    font-size:.75rem;
    text-transform:uppercase;
    letter-spacing:.06em;
    white-space:nowrap;
    font-weight:900;
    border-bottom:1px solid rgba(15,23,42,.08);
  }

  .profile-table tbody td{
    color:#334155;
    vertical-align:middle;
  }

  .profile-table tbody tr:hover{
    background:rgba(16,185,129,.045);
  }

  .type-badge{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.42rem .78rem;
    border-radius:999px;
    background:linear-gradient(135deg, #047857, #10b981);
    color:#fff;
    font-size:.78rem;
    font-weight:900;
  }

  .missing-badge{
    display:inline-flex;
    align-items:center;
    padding:.4rem .75rem;
    border-radius:999px;
    background:rgba(245,158,11,.12);
    color:#92400e;
    font-weight:900;
    font-size:.8rem;
  }

  .zero-muted{
    color:#94a3b8;
    font-size:.85rem;
    font-weight:800;
  }

  .alert-modern{
    border:1px solid rgba(245,158,11,.25);
    background:linear-gradient(135deg, rgba(255,251,235,.95), rgba(255,255,255,.95));
    color:#92400e;
    border-radius:22px;
    padding:1.25rem;
    box-shadow:0 18px 45px -36px rgba(245,158,11,.35);
  }

  .empty-card{
    border-radius:26px;
    padding:3rem 1.25rem;
    background:rgba(255,255,255,.92);
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 22px 60px -38px rgba(4,120,87,.30);
    text-align:center;
  }

  .empty-icon{
    width:86px;
    height:86px;
    margin:0 auto 1rem;
    border-radius:26px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(16,185,129,.10);
    color:#047857;
    font-size:2.5rem;
  }

  .action-row{
    display:flex;
    gap:1rem;
    align-items:center;
    flex-wrap:wrap;
  }

  @media (max-width: 991px){
    .profile-hero{
      padding:2rem;
    }

    .card-body-custom{
      padding:1.2rem;
    }
  }
</style>

<div class="profile-page">

  <div class="profile-hero mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <span class="eyebrow">
          <i class="bi bi-search"></i>
          Step 2 · Data Profiling
        </span>

        <h1 class="profile-title">
          Understand Your Dataset
        </h1>

        <?php if ($file): ?>
          <p class="profile-subtitle">
            Comprehensive profile of your uploaded dataset. The system identifies structure, quality issues, missing values, duplicates, and column-level statistics.
          </p>
        <?php else: ?>
          <p class="profile-subtitle">
            This step displays the structure, quality, missing values, duplicates, and column information of your uploaded dataset.
          </p>
        <?php endif; ?>
      </div>

      <?php if ($file): ?>
        <div class="file-chip-main">
          <i class="bi bi-file-earmark-spreadsheet text-success"></i>
          <span><?= htmlspecialchars($file['original_name']) ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($alert_message): ?>

    <div class="alert-modern mb-4">
      <div class="d-flex align-items-start gap-3">
        <div>
          <i class="bi bi-exclamation-triangle-fill" style="font-size:2rem;"></i>
        </div>

        <div>
          <h5 class="fw-bold mb-1">Dataset Required</h5>
          <p class="mb-3">
            <?= htmlspecialchars($alert_message) ?>
          </p>

          <a href="upload.php" class="btn btn-warning fw-bold">
            <i class="bi bi-cloud-arrow-up me-2"></i>
            Upload Dataset First
          </a>
        </div>
      </div>
    </div>

    <div class="empty-card">
      <div class="empty-icon">
        <i class="bi bi-file-earmark-spreadsheet"></i>
      </div>

      <h3 class="fw-bold mb-2">
        No Dataset Available for Profiling
      </h3>

      <p class="text-muted mb-4" style="max-width:620px; margin:auto;">
        The profiling module needs an uploaded file before it can analyze rows, columns, missing values, duplicates, and data types.
      </p>

      <div class="d-flex justify-content-center gap-2 flex-wrap">
        <a href="upload.php" class="btn btn-primary btn-lg">
          <i class="bi bi-cloud-arrow-up me-2"></i>
          Go to Upload
        </a>

        <a href="home.php" class="btn btn-outline-secondary btn-lg">
          <i class="bi bi-house me-2"></i>
          Back to Home
        </a>
      </div>
    </div>

  <?php else: ?>

    <div class="row g-3 mb-4">

      <div class="col-md-6 col-lg-3">
        <div class="stat-card p-4" style="--accent:#047857; --icon-bg:rgba(16,185,129,.10);">
          <div class="d-flex align-items-center">
            <div class="stat-icon">
              <i class="bi bi-list"></i>
            </div>

            <div class="ms-3">
              <div class="stat-label">Rows</div>
              <div class="stat-value"><?= number_format(count($rows)) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="stat-card p-4" style="--accent:#10b981; --icon-bg:rgba(16,185,129,.10);">
          <div class="d-flex align-items-center">
            <div class="stat-icon">
              <i class="bi bi-columns-gap"></i>
            </div>

            <div class="ms-3">
              <div class="stat-label">Columns</div>
              <div class="stat-value"><?= number_format(count($headers)) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="stat-card p-4" style="--accent:#dc3545; --icon-bg:rgba(220,53,69,.10);">
          <div class="d-flex align-items-center">
            <div class="stat-icon">
              <i class="bi bi-exclamation-circle"></i>
            </div>

            <div class="ms-3">
              <div class="stat-label">Duplicates</div>
              <div class="stat-value"><?= number_format($dups) ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="stat-card p-4" style="--accent:#f59e0b; --icon-bg:rgba(245,158,11,.12);">
          <div class="d-flex align-items-center">
            <div class="stat-icon">
              <i class="bi bi-question-circle"></i>
            </div>

            <div class="ms-3">
              <div class="stat-label">Missing</div>
              <div class="stat-value"><?= number_format($total_missing) ?></div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="glass-card mb-4">
      <div class="card-head-custom">
        <h5 class="card-title-custom">
          <i class="bi bi-table text-success"></i>
          Column Analysis
        </h5>
        <p class="card-subtitle">
          Summary of each column including detected type, missing values, unique count, frequent values, and numeric range.
        </p>
      </div>

      <div class="card-body-custom">
        <div class="table-shell table-responsive">
          <table class="table table-sm table-striped align-middle profile-table">
            <thead>
              <tr>
                <th><i class="bi bi-type me-2"></i>Column</th>
                <th><i class="bi bi-gear me-2"></i>Type</th>
                <th><i class="bi bi-question-circle me-2"></i>Missing</th>
                <th><i class="bi bi-asterisk me-2"></i>Unique</th>
                <th><i class="bi bi-star me-2"></i>Most Frequent</th>
                <th><i class="bi bi-arrow-down-up me-2"></i>Min / Max</th>
                <th><i class="bi bi-calculator me-2"></i>Avg</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($profile as $p): ?>
                <tr>
                  <td class="fw-semibold">
                    <?= htmlspecialchars($p['name']) ?>
                  </td>

                  <td>
                    <span class="type-badge">
                      <i class="bi bi-tag"></i>
                      <?= htmlspecialchars($p['type']) ?>
                    </span>
                  </td>

                  <td>
                    <?php if ((int)$p['missing'] > 0): ?>
                      <span class="missing-badge">
                        <?= (int)$p['missing'] ?>
                      </span>
                    <?php else: ?>
                      <span class="zero-muted">0</span>
                    <?php endif; ?>
                  </td>

                  <td><?= number_format((int)$p['unique']) ?></td>

                  <td><?= htmlspecialchars((string)$p['most_frequent']) ?></td>

                  <td class="text-muted small">
                    <?= htmlspecialchars((string)$p['min']) ?> → <?= htmlspecialchars((string)$p['max']) ?>
                  </td>

                  <td><?= htmlspecialchars((string)$p['avg']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="glass-card mb-4">
      <div class="card-head-custom">
        <h5 class="card-title-custom">
          <i class="bi bi-eye text-success"></i>
          Data Preview
        </h5>
        <p class="card-subtitle">
          First 20 rows of the uploaded dataset for quick inspection.
        </p>
      </div>

      <div class="card-body-custom">
        <div class="table-shell scroll-x">
          <table class="table table-sm table-bordered table-preview profile-table mb-0">
            <thead>
              <tr>
                <?php foreach ($headers as $h): ?>
                  <th><?= htmlspecialchars($h) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>

            <tbody>
              <?php foreach (array_slice($rows, 0, 20) as $row): ?>
                <tr>
                  <?php foreach ($row as $cell): ?>
                    <td><?= htmlspecialchars((string)$cell) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="action-row mb-4">
      <a href="clean.php" class="btn btn-primary btn-lg">
        <i class="bi bi-magic me-2"></i>
        Proceed to Cleaning
      </a>

      <a href="home.php" class="btn btn-outline-secondary btn-lg">
        <i class="bi bi-house me-2"></i>
        Back to Home
      </a>
    </div>

  <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>