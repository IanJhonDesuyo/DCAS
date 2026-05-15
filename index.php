<?php
session_start();

// Demo/guest mode so home.php and included header can work without login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Guest';
    $_SESSION['user_name'] = 'Guest';
    $_SESSION['role'] = 'guest';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>CleanSight · Data Cleaning &amp; Analytics System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

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

  html,
  body{
    min-height:100%;
  }

  body{
    margin:0;
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
    color:var(--ink);
    background:
      radial-gradient(700px 360px at 8% 8%, rgba(16,185,129,.14), transparent 60%),
      radial-gradient(600px 320px at 92% 12%, rgba(20,184,166,.14), transparent 60%),
      linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 45%, #f8fafc 100%);
    overflow-x:hidden;
  }

  .landing-shell{
    min-height:100vh;
    position:relative;
    isolation:isolate;
    display:flex;
    flex-direction:column;
  }

  .top-nav{
    width:100%;
    padding:1.1rem 2rem;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    position:relative;
    z-index:5;
  }

  .brand-logo{
    display:flex;
    align-items:center;
    gap:.75rem;
    text-decoration:none;
    color:var(--ink);
  }

  .logo-box{
    width:44px;
    height:44px;
    border-radius:15px;
    background:var(--brand-gradient-dark);
    color:#d1fae5;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 14px 34px -22px rgba(4,120,87,.8);
  }

  .logo-box i{
    font-size:1.45rem;
  }

  .brand-text strong{
    display:block;
    font-size:1.05rem;
    font-weight:900;
    line-height:1.05;
    color:#064e3b;
  }

  .brand-text span{
    display:block;
    color:#64748b;
    font-size:.8rem;
    margin-top:.1rem;
  }

  .nav-pill{
    text-decoration:none;
    color:#065f46;
    font-weight:800;
    padding:.65rem 1rem;
    border-radius:999px;
    background:rgba(255,255,255,.72);
    border:1px solid rgba(4,120,87,.14);
    box-shadow:0 12px 28px -24px rgba(4,120,87,.45);
    transition:transform .15s ease, background .15s ease;
  }

  .nav-pill:hover{
    color:#047857;
    background:#fff;
    transform:translateY(-1px);
  }

  .hero-section{
    flex:1;
    display:grid;
    grid-template-columns:1.08fr .92fr;
    align-items:center;
    gap:2.5rem;
    padding:2rem;
    max-width:1240px;
    width:100%;
    margin:0 auto;
  }

  .hero-card{
    position:relative;
    border-radius:34px;
    padding:3rem;
    background:linear-gradient(135deg, rgba(255,255,255,.92), rgba(255,255,255,.68));
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 30px 90px -54px rgba(4,120,87,.55);
    backdrop-filter:blur(12px);
    overflow:hidden;
  }

  .hero-card::after{
    content:"";
    position:absolute;
    right:-110px;
    top:-120px;
    width:330px;
    height:330px;
    border-radius:50%;
    background:conic-gradient(from 120deg, var(--brand-dark), var(--brand-3), var(--brand-1), var(--brand-dark));
    filter:blur(62px);
    opacity:.30;
  }

  .hero-card > *{
    position:relative;
    z-index:1;
  }

  .eyebrow{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    padding:.45rem .85rem;
    border-radius:999px;
    background:rgba(16,185,129,.10);
    color:#047857;
    font-weight:900;
    font-size:.78rem;
    letter-spacing:.06em;
    text-transform:uppercase;
    border:1px solid rgba(16,185,129,.18);
  }

  .hero-title{
    margin:1rem 0 .75rem;
    font-size:clamp(2.45rem, 5vw, 4.5rem);
    line-height:1.02;
    font-weight:950;
    letter-spacing:-.065em;
    color:transparent;
    background:linear-gradient(120deg, #052e2b 0%, #047857 50%, #10b981 80%, #14b8a6 100%);
    -webkit-background-clip:text;
    background-clip:text;
  }

  .hero-subtitle{
    color:var(--ink-soft);
    font-size:1.12rem;
    line-height:1.65;
    max-width:680px;
    margin:0;
  }

  .hero-actions{
    display:flex;
    flex-wrap:wrap;
    gap:.9rem;
    margin-top:2rem;
  }

  .btn-start{
    min-height:54px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.6rem;
    padding:0 1.35rem;
    border-radius:16px;
    background:var(--brand-gradient);
    border:none;
    color:#fff;
    font-weight:900;
    text-decoration:none;
    box-shadow:0 18px 40px -22px rgba(4,120,87,.9);
    transition:transform .15s ease, filter .15s ease, box-shadow .15s ease;
  }

  .btn-start:hover{
    color:#fff;
    transform:translateY(-2px);
    filter:brightness(1.04);
    box-shadow:0 22px 48px -24px rgba(4,120,87,1);
  }

  .btn-secondary-soft{
    min-height:54px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.6rem;
    padding:0 1.25rem;
    border-radius:16px;
    background:rgba(255,255,255,.76);
    border:1px solid rgba(4,120,87,.18);
    color:#065f46;
    font-weight:900;
    text-decoration:none;
    transition:transform .15s ease, background .15s ease;
  }

  .btn-secondary-soft:hover{
    color:#047857;
    background:#fff;
    transform:translateY(-2px);
  }

  .workflow-strip{
    display:flex;
    flex-wrap:wrap;
    gap:.6rem;
    margin-top:2rem;
  }

  .workflow-pill{
    display:inline-flex;
    align-items:center;
    gap:.45rem;
    padding:.55rem .82rem;
    border-radius:999px;
    background:#fff;
    border:1px solid rgba(15,23,42,.07);
    color:#334155;
    font-weight:800;
    font-size:.86rem;
    box-shadow:0 10px 24px -22px rgba(15,23,42,.35);
  }

  .workflow-num{
    width:24px;
    height:24px;
    border-radius:50%;
    background:var(--brand-gradient);
    color:#fff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:.75rem;
    font-weight:950;
  }

  .visual-panel{
    position:relative;
    min-height:560px;
    border-radius:36px;
    padding:1.2rem;
    background:
      radial-gradient(360px 190px at 30% 0%, rgba(110,231,183,.24), transparent 60%),
      var(--brand-gradient-dark);
    box-shadow:0 34px 90px -52px rgba(4,120,87,.9);
    overflow:hidden;
  }

  .dashboard-preview{
    position:relative;
    height:100%;
    min-height:535px;
    border-radius:28px;
    padding:1.3rem;
    background:rgba(255,255,255,.10);
    border:1px solid rgba(255,255,255,.14);
    backdrop-filter:blur(12px);
    color:#fff;
  }

  .preview-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:1rem;
    margin-bottom:1rem;
  }

  .preview-title{
    font-weight:900;
    font-size:1rem;
  }

  .preview-status{
    padding:.35rem .65rem;
    border-radius:999px;
    background:rgba(110,231,183,.16);
    color:#d1fae5;
    border:1px solid rgba(209,250,229,.18);
    font-size:.78rem;
    font-weight:800;
  }

  .preview-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:.8rem;
    margin-bottom:.9rem;
  }

  .preview-metric{
    border-radius:18px;
    padding:1rem;
    background:rgba(255,255,255,.11);
    border:1px solid rgba(255,255,255,.13);
  }

  .preview-metric .label{
    color:rgba(255,255,255,.62);
    font-size:.72rem;
    text-transform:uppercase;
    letter-spacing:.08em;
    font-weight:800;
  }

  .preview-metric .value{
    margin-top:.35rem;
    font-size:1.85rem;
    font-weight:950;
    color:#d1fae5;
  }

  .chart-mock{
    border-radius:22px;
    padding:1.1rem;
    background:rgba(255,255,255,.11);
    border:1px solid rgba(255,255,255,.13);
    margin-bottom:.9rem;
  }

  .chart-bars{
    height:150px;
    display:flex;
    align-items:end;
    gap:.65rem;
    padding-top:1rem;
  }

  .bar{
    flex:1;
    border-radius:999px 999px 8px 8px;
    background:linear-gradient(180deg, #6ee7b7, #10b981);
    min-height:28px;
  }

  .bar:nth-child(1){ height:45%; }
  .bar:nth-child(2){ height:70%; }
  .bar:nth-child(3){ height:55%; }
  .bar:nth-child(4){ height:86%; }
  .bar:nth-child(5){ height:62%; }
  .bar:nth-child(6){ height:76%; }

  .insight-mock{
    border-radius:20px;
    padding:1rem;
    background:rgba(209,250,229,.11);
    border:1px solid rgba(209,250,229,.16);
    color:rgba(255,255,255,.82);
    line-height:1.55;
  }

  .insight-mock strong{
    color:#d1fae5;
  }

  .feature-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:1rem;
    max-width:1240px;
    width:100%;
    margin:0 auto;
    padding:0 2rem 2rem;
  }

  .feature-card{
    border-radius:24px;
    padding:1.25rem;
    background:rgba(255,255,255,.82);
    border:1px solid rgba(15,23,42,.07);
    box-shadow:0 18px 50px -36px rgba(4,120,87,.32);
    backdrop-filter:blur(10px);
  }

  .feature-icon{
    width:44px;
    height:44px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#047857;
    background:rgba(16,185,129,.10);
    margin-bottom:.85rem;
  }

  .feature-icon i{
    font-size:1.35rem;
  }

  .feature-card h5{
    margin:0 0 .35rem;
    font-weight:900;
    color:#0f172a;
  }

  .feature-card p{
    margin:0;
    color:#64748b;
    font-size:.92rem;
    line-height:1.55;
  }

  .footer-note{
    text-align:center;
    color:#64748b;
    font-size:.86rem;
    padding:0 1rem 1.5rem;
  }

  @media (max-width: 991px){
    .top-nav{
      padding:1rem;
    }

    .hero-section{
      grid-template-columns:1fr;
      padding:1rem;
    }

    .hero-card{
      padding:2rem;
    }

    .visual-panel{
      min-height:auto;
    }

    .dashboard-preview{
      min-height:auto;
    }

    .feature-grid{
      grid-template-columns:1fr;
      padding:0 1rem 1rem;
    }
  }

  @media (max-width: 576px){
    .hero-actions{
      flex-direction:column;
    }

    .btn-start,
    .btn-secondary-soft{
      width:100%;
    }

    .preview-grid{
      grid-template-columns:1fr;
    }
  }
</style>
</head>

<body>

<div class="landing-shell">

  <nav class="top-nav">
    <a href="index.php" class="brand-logo">
      <div class="logo-box">
        <i class="bi bi-stars"></i>
      </div>

      <div class="brand-text">
        <strong>CleanSight</strong>
        <span>Data Cleaning &amp; Analytics System</span>
      </div>
    </a>

    <a href="home.php" class="nav-pill">
      <i class="bi bi-house-door me-1"></i>
      Open System
    </a>
  </nav>

  <section class="hero-section">

    <div class="hero-card">
      <span class="eyebrow">
        <i class="bi bi-database-check"></i>
        Data Cleaning Made Simple
      </span>

      <h1 class="hero-title">
        Clean, analyze, and visualize your data in one flow.
      </h1>

      <p class="hero-subtitle">
        CleanSight helps users transform raw datasets into cleaner, more understandable information through profiling, cleaning operations, generated insights, and interactive dashboard visualizations.
      </p>

      <div class="hero-actions">
        <a href="home.php" class="btn-start">
          <i class="bi bi-arrow-right-circle"></i>
          Get Started
        </a>

        <a href="upload.php" class="btn-secondary-soft">
          <i class="bi bi-cloud-arrow-up"></i>
          Upload Dataset
        </a>
      </div>

      <div class="workflow-strip">
        <span class="workflow-pill"><span class="workflow-num">1</span>Upload</span>
        <span class="workflow-pill"><span class="workflow-num">2</span>Profile</span>
        <span class="workflow-pill"><span class="workflow-num">3</span>Clean</span>
        <span class="workflow-pill"><span class="workflow-num">4</span>Analyze</span>
        <span class="workflow-pill"><span class="workflow-num">5</span>Visualize</span>
      </div>
    </div>

    <div class="visual-panel">
      <div class="dashboard-preview">
        <div class="preview-top">
          <div class="preview-title">
            <i class="bi bi-graph-up-arrow me-2"></i>
            Dashboard Preview
          </div>

          <div class="preview-status">
            Ready for Analysis
          </div>
        </div>

        <div class="preview-grid">
          <div class="preview-metric">
            <div class="label">Records</div>
            <div class="value">1,250</div>
          </div>

          <div class="preview-metric">
            <div class="label">Fields</div>
            <div class="value">12</div>
          </div>

          <div class="preview-metric">
            <div class="label">Cleaned</div>
            <div class="value">98%</div>
          </div>

          <div class="preview-metric">
            <div class="label">Insights</div>
            <div class="value">24</div>
          </div>
        </div>

        <div class="chart-mock">
          <div class="d-flex justify-content-between align-items-center">
            <strong>Visual Summary</strong>
            <small style="color:rgba(255,255,255,.58);">Auto-generated</small>
          </div>

          <div class="chart-bars">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
          </div>
        </div>

        <div class="insight-mock">
          <strong>Insight:</strong>
          The highest category contributes the largest share of the displayed data. Cleaning improves the reliability of this analysis by reducing duplicates, missing values, and invalid entries.
        </div>
      </div>
    </div>

  </section>

  <section class="feature-grid">
    <div class="feature-card">
      <div class="feature-icon">
        <i class="bi bi-search"></i>
      </div>
      <h5>Profile Data</h5>
      <p>Detect rows, columns, data types, missing values, unique values, and basic statistics.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon">
        <i class="bi bi-magic"></i>
      </div>
      <h5>Clean Records</h5>
      <p>Remove duplicates, handle missing values, standardize dates, trim spaces, and filter invalid rows.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon">
        <i class="bi bi-bar-chart-line"></i>
      </div>
      <h5>Generate Insights</h5>
      <p>View summaries, comparisons, dynamic chart interpretations, and visual dashboard outputs.</p>
    </div>
  </section>

  <div class="footer-note">
    © <?= date('Y') ?> CleanSight · Data Cleaning and Analytics System
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>