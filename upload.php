<?php
session_start();

require_once 'includes/db.php';
require_once 'includes/helpers.php';

/*
  Guest/demo mode since login was removed.
*/
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Guest';
    $_SESSION['user_name'] = 'Guest';
    $_SESSION['role'] = 'guest';
}

$msg = '';
$err = '';
$user_id = (int)$_SESSION['user_id'];

$uploads_dir = dcas_uploads_dir();

if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

/*
  Use sample dataset
*/
if (isset($_GET['sample'])) {
    $src = __DIR__ . '/sample/sales_sample.csv';
    $name = 'sales_sample.csv';
    $stored = uniqid('ds_') . '.csv';
    $dest = $uploads_dir . '/' . $stored;

    if (!file_exists($src)) {
        $err = 'Sample dataset was not found. Please check if sample/sales_sample.csv exists.';
    } elseif (!copy($src, $dest)) {
        $err = 'Could not copy the sample dataset into the uploads folder.';
    } else {
        [$h, $rows] = read_csv($dest);

        if (count($h) === 0) {
            $err = 'The sample CSV appears to be empty or unreadable.';
            @unlink($dest);
        } else {
            $size = filesize($dest);
            $rc = count($rows);
            $cc = count($h);

            $stmt = $conn->prepare("
                INSERT INTO uploaded_files 
                (user_id, original_name, stored_name, file_size, rows_count, cols_count) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                $err = 'Database prepare failed: ' . $conn->error;
                @unlink($dest);
            } else {
                $stmt->bind_param('issiii', $user_id, $name, $stored, $size, $rc, $cc);

                if ($stmt->execute()) {
                    $_SESSION['file_id'] = $stmt->insert_id;

                    log_action($conn, $_SESSION['file_id'], 'upload_sample', 'Loaded built-in sample dataset');

                    header('Location: profile.php');
                    exit;
                } else {
                    $err = 'Database insert failed: ' . $stmt->error;
                    @unlink($dest);
                }
            }
        }
    }
}

/*
  Handle normal upload
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['dataset']['name'])) {
    $f = $_FILES['dataset'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $err = 'Upload failed. Error code: ' . $f['error'];
    } elseif (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
        $err = 'Only .csv, .xlsx, and .xls files are accepted.';
    } elseif (in_array($ext, ['xlsx', 'xls'])) {
        $err = 'Excel files are not processed directly. Please open the file in Excel and use Save As → CSV (Comma delimited), then upload the CSV file.';
    } else {
        $stored = uniqid('ds_') . '.csv';
        $path = $uploads_dir . '/' . $stored;

        if (!move_uploaded_file($f['tmp_name'], $path)) {
            $err = 'Could not save the uploaded file. Please check permissions on the uploads folder.';
        } else {
            [$h, $rows] = read_csv($path);

            if (count($h) === 0) {
                $err = 'The CSV appears to be empty or unreadable.';
                @unlink($path);
            } else {
                $rc = count($rows);
                $cc = count($h);
                $size = filesize($path);
                $orig = $f['name'];

                $stmt = $conn->prepare("
                    INSERT INTO uploaded_files 
                    (user_id, original_name, stored_name, file_size, rows_count, cols_count) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                if (!$stmt) {
                    $err = 'Database prepare failed: ' . $conn->error;
                    @unlink($path);
                } else {
                    $stmt->bind_param('issiii', $user_id, $orig, $stored, $size, $rc, $cc);

                    if ($stmt->execute()) {
                        $_SESSION['file_id'] = $stmt->insert_id;

                        log_action($conn, $_SESSION['file_id'], 'upload', "Uploaded $orig");

                        header('Location: profile.php');
                        exit;
                    } else {
                        $err = 'Database insert failed: ' . $stmt->error;
                        @unlink($path);
                    }
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<style>
  .upload-page{
    position:relative;
    isolation:isolate;
  }

  .upload-page::before{
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

  .upload-hero{
    position:relative;
    border-radius:30px;
    padding:3rem;
    background:linear-gradient(135deg, rgba(255,255,255,.90), rgba(255,255,255,.66));
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 30px 80px -45px rgba(4,120,87,.45), 0 8px 24px -14px rgba(15,23,42,.08);
    backdrop-filter:blur(10px);
    overflow:hidden;
  }

  .upload-hero::after{
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

  .upload-hero > *{
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

  .upload-title{
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

  .upload-subtitle{
    color:#475569;
    font-size:1.05rem;
    max-width:760px;
    margin:0;
  }

  .glass-card{
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
    padding:1.55rem;
  }

  .drop-zone{
    position:relative;
    border:2px dashed rgba(16,185,129,.45);
    border-radius:24px;
    padding:2.4rem 1.5rem;
    text-align:center;
    background:
      radial-gradient(400px 140px at 50% 0%, rgba(110,231,183,.12), transparent 60%),
      linear-gradient(135deg, rgba(16,185,129,.045), rgba(20,184,166,.055));
    cursor:pointer;
    transition:all .2s ease;
  }

  .drop-zone:hover,
  .drop-zone.dragging{
    transform:translateY(-2px);
    border-color:#047857;
    background:
      radial-gradient(400px 140px at 50% 0%, rgba(110,231,183,.18), transparent 60%),
      linear-gradient(135deg, rgba(16,185,129,.09), rgba(20,184,166,.08));
  }

  .upload-icon-wrap{
    width:84px;
    height:84px;
    margin:0 auto 1rem;
    border-radius:24px;
    background:linear-gradient(135deg, rgba(16,185,129,.12), rgba(20,184,166,.12));
    display:flex;
    align-items:center;
    justify-content:center;
    color:#047857;
  }

  .upload-icon-wrap i{
    font-size:3rem;
  }

  .selected-file{
    margin-top:1rem;
    padding:.85rem 1rem;
    border-radius:16px;
    background:#fff;
    border:1px solid rgba(15,23,42,.08);
    color:#334155;
    display:none;
    align-items:center;
    justify-content:center;
    gap:.55rem;
    font-weight:800;
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

  .btn-sample{
    min-height:52px;
    border-radius:15px;
    font-weight:900;
    background:rgba(255,255,255,.76);
    border:1px solid rgba(16,185,129,.28);
    color:#047857;
    transition:transform .15s ease, background .15s ease;
  }

  .btn-sample:hover{
    transform:translateY(-2px);
    background:rgba(16,185,129,.08);
    color:#065f46;
    border-color:rgba(16,185,129,.40);
  }

  .helper-note{
    margin-top:1rem;
    padding:.95rem 1rem;
    border-radius:18px;
    background:rgba(16,185,129,.06);
    border:1px solid rgba(16,185,129,.10);
    color:#475569;
    font-size:.9rem;
    text-align:center;
  }

  .side-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:24px;
    background:linear-gradient(180deg, #fff, #fbfffd);
    box-shadow:0 22px 60px -38px rgba(4,120,87,.38);
    overflow:hidden;
  }

  .side-card-head{
    padding:1.15rem 1.25rem;
    border-bottom:1px solid rgba(15,23,42,.06);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .side-card-title{
    margin:0;
    color:#0f172a;
    font-weight:950;
    display:flex;
    align-items:center;
    gap:.55rem;
  }

  .step-tile{
    height:100%;
    border-radius:18px;
    padding:1rem;
    text-align:center;
    background:linear-gradient(135deg, rgba(16,185,129,.08), rgba(20,184,166,.08));
    border:1px solid rgba(16,185,129,.10);
    transition:transform .15s ease, box-shadow .15s ease;
  }

  .step-tile:hover{
    transform:translateY(-3px);
    box-shadow:0 14px 34px -28px rgba(4,120,87,.6);
  }

  .step-tile i{
    display:block;
    font-size:1.85rem;
    color:#047857;
    margin-bottom:.55rem;
  }

  .step-tile p{
    margin:0;
    font-size:.86rem;
    font-weight:900;
    color:#334155;
  }

  .feature-list{
    list-style:none;
    margin:0;
    padding:0;
  }

  .feature-list li{
    display:flex;
    align-items:flex-start;
    gap:.75rem;
    padding:.8rem 0;
    border-bottom:1px solid rgba(15,23,42,.06);
  }

  .feature-list li:last-child{
    border-bottom:none;
    padding-bottom:0;
  }

  .feature-icon{
    width:34px;
    height:34px;
    border-radius:11px;
    background:rgba(16,185,129,.10);
    color:#047857;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
  }

  .feature-title{
    font-weight:900;
    color:#0f172a;
    margin-bottom:.15rem;
  }

  .feature-desc{
    color:#64748b;
    font-size:.86rem;
  }

  @media (max-width: 991px){
    .upload-hero{
      padding:2rem;
    }

    .card-body-custom{
      padding:1.2rem;
    }
  }
</style>

<div class="upload-page">

  <div class="upload-hero mb-4">
    <span class="eyebrow">
      <i class="bi bi-cloud-arrow-up-fill"></i>
      Step 1 · Upload Dataset
    </span>

    <h1 class="upload-title">
      Upload Your Dataset
    </h1>

    <p class="upload-subtitle">
      Start your data cleaning workflow by uploading a CSV file. Once uploaded, the system will prepare your dataset for profiling, cleaning, analysis, and visualization.
    </p>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-danger auto-dismiss">
      <i class="bi bi-exclamation-circle me-2"></i>
      <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <?php if ($msg): ?>
    <div class="alert alert-success auto-dismiss">
      <i class="bi bi-check-circle me-2"></i>
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <div class="col-lg-7">
      <div class="glass-card">
        <div class="card-head">
          <h5 class="card-title-custom">
            <i class="bi bi-file-earmark-arrow-up text-success"></i>
            Choose CSV File
          </h5>
          <p class="card-subtitle">
            Upload a clean or raw CSV file to begin the workflow.
          </p>
        </div>

        <div class="card-body-custom">
          <form method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="drop-zone" id="dropZone">
              <input
                type="file"
                name="dataset"
                id="fileInput"
                class="form-control"
                accept=".csv,.xls,.xlsx"
                required
                style="display:none;"
              >

              <div class="upload-icon-wrap">
                <i class="bi bi-cloud-arrow-up"></i>
              </div>

              <h5 class="fw-bold mb-2">
                Drag and drop your file here
              </h5>

              <p class="text-muted mb-1">
                or click anywhere in this box to browse
              </p>

              <p class="text-muted small mb-0">
                Accepted formats: CSV, XLS, XLSX
              </p>

              <div class="selected-file" id="selectedFile">
                <i class="bi bi-file-earmark-spreadsheet text-success"></i>
                <span id="selectedFileName">No file selected</span>
              </div>
            </div>

            <div class="d-grid gap-2 mt-4">
              <button type="submit" class="btn btn-hero btn-lg" id="submitBtn">
                <i class="bi bi-cloud-arrow-up me-2"></i>
                Upload Dataset
              </button>

              <a href="upload.php?sample=1" class="btn btn-sample btn-lg">
                <i class="bi bi-stars me-2"></i>
                Try Sample Dataset
              </a>
            </div>

            <div class="helper-note">
              <i class="bi bi-lightbulb me-1 text-success"></i>
              For Excel files, open the file in Excel and use
              <strong>File → Save As → CSV (Comma delimited)</strong> before uploading.
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="side-card mb-4">
        <div class="side-card-head">
          <h6 class="side-card-title">
            <i class="bi bi-lightning-charge text-success"></i>
            Quick Steps
          </h6>
        </div>

        <div class="p-4">
          <div class="row g-3">
            <div class="col-6">
              <div class="step-tile">
                <i class="bi bi-1-circle"></i>
                <p>Parse CSV</p>
              </div>
            </div>

            <div class="col-6">
              <div class="step-tile">
                <i class="bi bi-2-circle"></i>
                <p>Profile Data</p>
              </div>
            </div>

            <div class="col-6">
              <div class="step-tile">
                <i class="bi bi-3-circle"></i>
                <p>Clean Data</p>
              </div>
            </div>

            <div class="col-6">
              <div class="step-tile">
                <i class="bi bi-4-circle"></i>
                <p>Visualize</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="side-card">
        <div class="side-card-head">
          <h6 class="side-card-title">
            <i class="bi bi-info-circle text-success"></i>
            What We'll Do
          </h6>
        </div>

        <div class="p-4">
          <ul class="feature-list">
            <li>
              <div class="feature-icon">
                <i class="bi bi-check-circle"></i>
              </div>

              <div>
                <div class="feature-title">Detect Columns</div>
                <div class="feature-desc">Identify headers, rows, and basic data types.</div>
              </div>
            </li>

            <li>
              <div class="feature-icon">
                <i class="bi bi-check-circle"></i>
              </div>

              <div>
                <div class="feature-title">Find Issues</div>
                <div class="feature-desc">Spot missing values, duplicate rows, and quality problems.</div>
              </div>
            </li>

            <li>
              <div class="feature-icon">
                <i class="bi bi-check-circle"></i>
              </div>

              <div>
                <div class="feature-title">Generate Insights</div>
                <div class="feature-desc">Prepare summaries, analysis, and visual reports.</div>
              </div>
            </li>
          </ul>
        </div>
      </div>

    </div>

  </div>

</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const selectedFile = document.getElementById('selectedFile');
const selectedFileName = document.getElementById('selectedFileName');

function showSelectedFileName() {
  if (fileInput.files && fileInput.files.length > 0) {
    selectedFileName.textContent = fileInput.files[0].name;
    selectedFile.style.display = 'inline-flex';
  }
}

// FIX: Ignore clicks that originate from the submit button or the sample link
dropZone.addEventListener('click', (e) => {
  if (e.target.closest('button[type="submit"]') || e.target.closest('a')) return;
  fileInput.click();
});

fileInput.addEventListener('change', showSelectedFileName);

dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('dragging');
});

dropZone.addEventListener('dragleave', () => {
  dropZone.classList.remove('dragging');
});

dropZone.addEventListener('drop', (e) => {
  e.preventDefault();

  if (e.dataTransfer.files.length > 0) {
    fileInput.files = e.dataTransfer.files;
    showSelectedFileName();
  }

  dropZone.classList.remove('dragging');
});
</script>

<?php require_once 'includes/footer.php'; ?>