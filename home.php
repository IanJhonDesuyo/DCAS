<?php
session_start();

require_once 'includes/db.php';
require_once 'includes/header.php';

// Overall system totals because login/user-specific access is removed
$total_files = $conn->query('SELECT COUNT(*) AS c FROM uploaded_files')->fetch_assoc()['c'] ?? 0;
$total_clean = $conn->query('SELECT COUNT(*) AS c FROM cleaning_logs')->fetch_assoc()['c'] ?? 0;
$total_ins   = $conn->query('SELECT COUNT(*) AS c FROM insights')->fetch_assoc()['c'] ?? 0;

// Recent datasets from all uploads
$r = $conn->query('SELECT * FROM uploaded_files ORDER BY id DESC LIMIT 8');
?>

<style>
  :root{
    --brand:#10b981;
    --brand-dark:#047857;
    --brand-deep:#052e2b;
    --brand-1:#14b8a6;
    --brand-2:#10b981;
    --brand-3:#6ee7b7;

    --brand-light:#d1fae5;
    --brand-soft:#ecfdf5;
    --brand-gradient:linear-gradient(135deg, #047857 0%, #10b981 52%, #14b8a6 100%);
    --brand-gradient-dark:linear-gradient(135deg, #052e2b 0%, #064e3b 48%, #047857 100%);

    --ink:#0f172a;
    --ink-soft:#475569;
    --muted:#64748b;
  }

  .page-bg{
    position:relative;
    isolation:isolate;
  }

  .page-bg::before{
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

  .hero{
    position:relative;
    border-radius:30px;
    padding:3rem;
    background:linear-gradient(135deg, rgba(255,255,255,.90), rgba(255,255,255,.66));
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 30px 80px -45px rgba(4,120,87,.45), 0 8px 24px -14px rgba(15,23,42,.08);
    backdrop-filter:blur(10px);
    overflow:hidden;
  }

  .hero::after{
    content:"";
    position:absolute;
    right:-120px;
    top:-120px;
    width:340px;
    height:340px;
    border-radius:50%;
    background:conic-gradient(from 120deg, var(--brand-dark), var(--brand-3), var(--brand-1), var(--brand-dark));
    filter:blur(60px);
    opacity:.35;
    z-index:0;
  }

  .hero > *{
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

  .hero h1{
    background:linear-gradient(120deg, #052e2b 0%, #047857 48%, #10b981 78%, #14b8a6 100%);
    -webkit-background-clip:text;
    background-clip:text;
    color:transparent;
    line-height:1.05;
    font-weight:950;
    letter-spacing:-.05em;
  }

  .step-pill{
    display:inline-flex;
    align-items:center;
    gap:.55rem;
    padding:.55rem .9rem;
    border-radius:999px;
    background:#fff;
    border:1px solid rgba(15,23,42,.08);
    font-weight:800;
    color:var(--ink);
    font-size:.92rem;
    box-shadow:0 8px 24px -20px rgba(15,23,42,.25);
    transition:transform .2s ease, box-shadow .2s ease;
  }

  .step-pill:hover{
    transform:translateY(-2px);
    box-shadow:0 14px 30px -22px rgba(4,120,87,.55);
  }

  .step-num{
    width:26px;
    height:26px;
    border-radius:50%;
    background:var(--brand-gradient);
    color:#fff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:.8rem;
    font-weight:900;
  }

  .btn-hero{
    background:var(--brand-gradient);
    border:none;
    color:#fff;
    font-weight:900;
    border-radius:14px;
    box-shadow:0 14px 34px -18px rgba(4,120,87,.75);
    transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
  }

  .btn-hero:hover{
    transform:translateY(-2px);
    filter:brightness(1.04);
    color:#fff;
    box-shadow:0 18px 40px -20px rgba(4,120,87,.9);
  }

  .btn-ghost{
    background:rgba(255,255,255,.76);
    border:1px solid rgba(4,120,87,.18);
    backdrop-filter:blur(8px);
    color:#065f46;
    font-weight:900;
    border-radius:14px;
  }

  .btn-ghost:hover{
    background:#fff;
    color:#047857;
    border-color:rgba(4,120,87,.28);
  }

  .hero-visual{
    position:relative;
    border-radius:26px;
    padding:2.25rem;
    background:
      radial-gradient(360px 160px at 25% 0%, rgba(110,231,183,.16), transparent 60%),
      linear-gradient(135deg, rgba(16,185,129,.08), rgba(20,184,166,.08), rgba(110,231,183,.10));
    border:1px solid rgba(16,185,129,.16);
    overflow:hidden;
  }

  .hero-visual::before{
    content:"";
    position:absolute;
    inset:0;
    background:
      radial-gradient(circle at 20% 10%, rgba(110,231,183,.25), transparent 40%),
      radial-gradient(circle at 90% 90%, rgba(20,184,166,.22), transparent 40%);
    pointer-events:none;
  }

  .hero-visual > *{
    position:relative;
    z-index:1;
  }

  .floaty{
    animation:floaty 5s ease-in-out infinite;
  }

  @keyframes floaty{
    0%,100%{
      transform:translateY(0);
    }

    50%{
      transform:translateY(-8px);
    }
  }

  .stat-card{
    border:1px solid rgba(15,23,42,.07);
    border-radius:22px;
    background:linear-gradient(180deg, #fff, #fbfffd);
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    position:relative;
    overflow:hidden;
    box-shadow:0 18px 50px -36px rgba(4,120,87,.28);
  }

  .stat-card::before{
    content:"";
    position:absolute;
    left:0;
    top:0;
    bottom:0;
    width:5px;
    background:var(--accent, var(--brand-2));
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
  }

  .stat-value{
    font-size:2.2rem;
    font-weight:900;
    color:var(--accent, var(--brand-2));
    line-height:1;
  }

  .stat-label{
    font-size:.76rem;
    font-weight:900;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:var(--ink-soft);
  }

  .panel{
    border:1px solid rgba(15,23,42,.07);
    border-radius:24px;
    background:rgba(255,255,255,.92);
    box-shadow:0 18px 50px -36px rgba(4,120,87,.32);
    overflow:hidden;
    backdrop-filter:blur(8px);
  }

  .panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    padding:1.25rem 1.5rem;
    border-bottom:1px solid rgba(15,23,42,.07);
    background:linear-gradient(180deg, #fff, #fbfffd);
  }

  .panel-head h5{
    margin:0;
    font-weight:900;
    color:#0f172a;
  }

  .table thead th{
    font-size:.75rem;
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--ink-soft);
    font-weight:900;
    border-bottom:1px solid rgba(15,23,42,.08);
    background:#f8fafc;
  }

  .table tbody tr{
    transition:background .15s ease;
  }

  .table tbody tr:hover{
    background:rgba(16,185,129,.05);
  }

  .file-chip{
    width:38px;
    height:38px;
    border-radius:11px;
    background:linear-gradient(135deg, rgba(16,185,129,.12), rgba(20,184,166,.12));
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  .empty-state{
    padding:3rem 1rem;
    text-align:center;
    color:var(--ink-soft);
  }

  .empty-state .bi{
    font-size:2.5rem;
    color:#94a3b8;
  }

  .badge-soft-green{
    background:rgba(16,185,129,.10);
    color:#047857;
  }

  .badge-soft-teal{
    background:rgba(20,184,166,.11);
    color:#0f766e;
  }

  .badge-soft-muted{
    background:rgba(100,116,139,.12);
    color:#475569;
  }

  @media (max-width: 991px){
    .hero{
      padding:2rem;
    }

    .panel-head{
      flex-direction:column;
      align-items:flex-start;
    }
  }
</style>

<div class="page-bg">

  <!-- HERO -->
  <div class="hero mb-5">
    <div class="row g-4 align-items-center">

      <div class="col-lg-7">
        <span class="eyebrow">
          <i class="bi bi-stars"></i>
          End-to-end data workflow
        </span>

        <h1 class="fw-bold mt-3 mb-3 display-4">
          CleanSight<br>Data Cleaning &amp; Analytics
        </h1>

        <p class="lead mb-4" style="font-size:1.15rem; color:var(--ink-soft); max-width:560px;">
          Transform raw and messy datasets into cleaner, more understandable insights.
          Upload, profile, clean, analyze, and visualize your data in one simple workflow.
        </p>

        <div class="d-flex flex-wrap gap-2 mb-4">
          <span class="step-pill"><span class="step-num">1</span> Upload</span>
          <i class="bi bi-arrow-right text-muted align-self-center"></i>

          <span class="step-pill"><span class="step-num">2</span> Profile</span>
          <i class="bi bi-arrow-right text-muted align-self-center"></i>

          <span class="step-pill"><span class="step-num">3</span> Clean</span>
          <i class="bi bi-arrow-right text-muted align-self-center"></i>

          <span class="step-pill"><span class="step-num">4</span> Analyze</span>
          <i class="bi bi-arrow-right text-muted align-self-center"></i>

          <span class="step-pill"><span class="step-num">5</span> Visualize</span>
        </div>

        <div class="d-flex gap-3 flex-wrap">
          <a href="upload.php" class="btn btn-hero btn-lg px-4">
            <i class="bi bi-cloud-arrow-up me-2"></i>
            Start Upload
          </a>

          <a href="documentation.php" class="btn btn-ghost btn-lg px-4">
            <i class="bi bi-book me-2"></i>
            Documentation
          </a>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="hero-visual text-center">
          <div class="floaty">
            <i class="bi bi-database-check" style="font-size:4.5rem; background:linear-gradient(135deg, var(--brand-dark), var(--brand-2)); -webkit-background-clip:text; background-clip:text; color:transparent;"></i>
          </div>

          <h4 class="fw-bold mt-3 mb-2">
            Professional Data Pipeline
          </h4>

          <p class="mb-3" style="color:var(--ink-soft);">
            A structured workflow for preparing raw datasets for clearer analysis and visualization.
          </p>

          <div class="d-flex justify-content-center gap-2 flex-wrap">
            <span class="badge rounded-pill badge-soft-green">
              <i class="bi bi-shield-check me-1"></i>
              Reliable
            </span>

            <span class="badge rounded-pill badge-soft-teal">
              <i class="bi bi-lightning me-1"></i>
              Fast
            </span>

            <span class="badge rounded-pill badge-soft-green">
              <i class="bi bi-magic me-1"></i>
              Smart
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- STATS -->
  <div class="row g-4 mb-5">

    <div class="col-md-4">
      <div class="stat-card p-4 h-100" style="--accent:#047857; --icon-bg:rgba(16,185,129,.10);">
        <div class="d-flex align-items-center mb-3">
          <div class="stat-icon">
            <i class="bi bi-upload" style="font-size:1.5rem; color:#047857;"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Datasets</div>
            <div class="stat-value"><?= (int)$total_files ?></div>
          </div>
        </div>

        <p class="text-muted small mb-0">
          <i class="bi bi-arrow-up-right text-success"></i>
          Total uploaded files
        </p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="stat-card p-4 h-100" style="--accent:#10b981; --icon-bg:rgba(16,185,129,.10);">
        <div class="d-flex align-items-center mb-3">
          <div class="stat-icon">
            <i class="bi bi-stars" style="font-size:1.5rem; color:#10b981;"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Cleaning</div>
            <div class="stat-value"><?= (int)$total_clean ?></div>
          </div>
        </div>

        <p class="text-muted small mb-0">
          <i class="bi bi-check2-circle text-success"></i>
          Cleaning operations applied
        </p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="stat-card p-4 h-100" style="--accent:#14b8a6; --icon-bg:rgba(20,184,166,.11);">
        <div class="d-flex align-items-center mb-3">
          <div class="stat-icon">
            <i class="bi bi-lightbulb" style="font-size:1.5rem; color:#14b8a6;"></i>
          </div>

          <div class="ms-3">
            <div class="stat-label">Insights</div>
            <div class="stat-value"><?= (int)$total_ins ?></div>
          </div>
        </div>

        <p class="text-muted small mb-0">
          <i class="bi bi-eye text-success"></i>
          Generated data insights
        </p>
      </div>
    </div>

  </div>

  <!-- RECENT DATASETS -->
  <div class="panel mb-5">
    <div class="panel-head">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-clock-history" style="font-size:1.3rem; color:#047857;"></i>
        <h5>Recent Datasets</h5>
      </div>

      <a href="upload.php" class="btn btn-sm btn-hero">
        <i class="bi bi-plus-lg me-1"></i>
        New Upload
      </a>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;" class="ps-4">File</th>
            <th>Filename</th>
            <th style="width:120px;">Rows</th>
            <th style="width:120px;">Cols</th>
            <th style="width:200px;">Uploaded</th>
            <th style="width:120px;" class="text-end pe-4">Action</th>
          </tr>
        </thead>

        <tbody>
          <?php if (!$r || $r->num_rows === 0): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="bi bi-inbox d-block mb-2"></i>

                  <div class="fw-semibold mb-1">
                    No datasets yet
                  </div>

                  <div class="small">
                    Upload your first file to begin the pipeline.
                  </div>

                  <a href="upload.php" class="btn btn-hero mt-3">
                    <i class="bi bi-cloud-arrow-up me-2"></i>
                    Upload Dataset
                  </a>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php while ($f = $r->fetch_assoc()): ?>
              <tr>
                <td class="ps-4">
                  <span class="file-chip">
                    <i class="bi bi-file-earmark-spreadsheet text-success"></i>
                  </span>
                </td>

                <td>
                  <div class="fw-semibold">
                    <?= htmlspecialchars($f['original_name']) ?>
                  </div>

                  <div class="text-muted small">
                    ID #<?= (int)$f['id'] ?>
                  </div>
                </td>

                <td>
                  <span class="badge rounded-pill badge-soft-teal">
                    <?= number_format((int)$f['rows_count']) ?>
                  </span>
                </td>

                <td>
                  <span class="badge rounded-pill badge-soft-muted">
                    <?= (int)$f['cols_count'] ?>
                  </span>
                </td>

                <td class="text-muted small">
                  <i class="bi bi-calendar3 me-1"></i>
                  <?= date('M d, Y · g:i A', strtotime($f['uploaded_at'])) ?>
                </td>

                <td class="text-end pe-4">
                  <a class="btn btn-sm btn-hero" href="profile.php?id=<?= (int)$f['id'] ?>">
                    Open <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>

      </table>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>