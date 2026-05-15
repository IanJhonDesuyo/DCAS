<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current = basename($_SERVER['PHP_SELF']);

/*
  Guest/demo mode:
  Since login was removed, the system automatically uses a default guest session.
*/
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Guest';
    $_SESSION['user_name'] = 'Guest';
    $_SESSION['role'] = 'guest';
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'Guest');
$role = $_SESSION['role'] ?? 'guest';

function nav_active($p, $current) {
    return $p === $current ? 'active' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>CleanSight · Data Cleaning and Analytics System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">

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

  --success:#10b981;
  --warning:#f59e0b;
  --danger:#dc3545;
  --info:#14b8a6;
  --secondary:#64748b;

  --ink:#0f172a;
  --muted:#64748b;
  --light:#f8fafc;
  --lighter:#f0fdf4;

  --sidebar-width:270px;
  --topbar-height:86px;
}

*{
  box-sizing:border-box;
}

body{
  margin:0;
  background:
    radial-gradient(700px 360px at 8% 8%, rgba(16,185,129,.12), transparent 60%),
    radial-gradient(600px 320px at 90% 12%, rgba(20,184,166,.12), transparent 60%),
    linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 45%, #f8fafc 100%);
  color:var(--ink);
  font-family:'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
  min-height:100vh;
}

/* =========================
   TOP BAR
========================= */

.modern-topbar{
  min-height:var(--topbar-height);
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:1rem 1.5rem;
  background:
    radial-gradient(520px 160px at 8% 0%, rgba(110,231,183,.24), transparent 60%),
    radial-gradient(420px 180px at 92% 10%, rgba(20,184,166,.28), transparent 60%),
    var(--brand-gradient-dark);
  color:#fff;
  border-bottom:1px solid rgba(255,255,255,.14);
  box-shadow:0 22px 55px -38px rgba(4,120,87,.95);
  position:sticky;
  top:0;
  z-index:1030;
}

.topbar-brand{
  display:flex;
  align-items:center;
  gap:.9rem;
  min-width:0;
  text-decoration:none;
  color:#fff;
}

.topbar-brand:hover{
  color:#fff;
}

.topbar-logo{
  width:52px;
  height:52px;
  border-radius:18px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:
    radial-gradient(circle at 30% 20%, rgba(110,231,183,.40), transparent 45%),
    linear-gradient(135deg, rgba(255,255,255,.98), rgba(209,250,229,.92));
  color:#064e3b;
  box-shadow:0 16px 34px -20px rgba(255,255,255,.80);
  flex-shrink:0;
}

.topbar-logo i{
  font-size:1.65rem;
}

.topbar-title{
  display:flex;
  align-items:baseline;
  gap:.45rem;
  flex-wrap:wrap;
  line-height:1.05;
}

.topbar-title strong{
  font-size:1.25rem;
  font-weight:950;
  letter-spacing:.01em;
}

.topbar-title span{
  font-size:.96rem;
  font-weight:500;
  color:rgba(255,255,255,.82);
}

.topbar-subtitle{
  margin-top:.25rem;
  color:rgba(255,255,255,.64);
  font-size:.8rem;
}

.topbar-actions{
  display:flex;
  align-items:center;
  gap:.65rem;
  flex-wrap:wrap;
}

.user-chip{
  display:flex;
  align-items:center;
  gap:.6rem;
  padding:.5rem .78rem;
  border-radius:999px;
  background:rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.16);
  backdrop-filter:blur(10px);
  color:rgba(255,255,255,.92);
}

.user-avatar{
  width:34px;
  height:34px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg, #6ee7b7, #14b8a6);
  color:#052e2b;
  font-weight:950;
  font-size:.85rem;
  flex-shrink:0;
}

.user-chip-name{
  font-size:.88rem;
  font-weight:850;
  line-height:1.1;
}

.user-chip-role{
  font-size:.72rem;
  color:rgba(255,255,255,.60);
  line-height:1.1;
  text-transform:capitalize;
}

.topbar-btn{
  display:inline-flex;
  align-items:center;
  gap:.45rem;
  height:42px;
  padding:0 .95rem;
  border-radius:999px;
  font-weight:850;
  font-size:.88rem;
  text-decoration:none;
  color:rgba(255,255,255,.92);
  border:1px solid rgba(255,255,255,.16);
  background:rgba(255,255,255,.08);
  backdrop-filter:blur(8px);
  transition:transform .15s ease, background .15s ease, color .15s ease;
}

.topbar-btn:hover{
  color:#fff;
  background:rgba(255,255,255,.16);
  transform:translateY(-1px);
}

.topbar-btn-primary{
  color:#052e2b;
  background:linear-gradient(135deg, #d1fae5, #6ee7b7);
  border-color:rgba(209,250,229,.30);
  box-shadow:0 14px 30px -22px rgba(110,231,183,.85);
}

.topbar-btn-primary:hover{
  color:#052e2b;
  background:linear-gradient(135deg, #ecfdf5, #6ee7b7);
}

/* =========================
   LAYOUT + SIDEBAR
========================= */

.app-layout{
  display:flex;
  align-items:stretch;
  min-height:calc(100vh - var(--topbar-height));
}

.sidebar{
  width:var(--sidebar-width);
  min-height:calc(100vh - var(--topbar-height));
  position:sticky;
  top:var(--topbar-height);
  align-self:flex-start;
  background:
    radial-gradient(260px 140px at 20% 0%, rgba(110,231,183,.16), transparent 65%),
    linear-gradient(180deg, rgba(255,255,255,.96), rgba(240,253,244,.92));
  border-right:1px solid rgba(15,23,42,.08);
  box-shadow:12px 0 40px -36px rgba(4,120,87,.45);
  overflow-y:auto;
  padding:1rem;
}

.sidebar-panel{
  padding:.95rem;
  border-radius:24px;
  background:rgba(255,255,255,.80);
  border:1px solid rgba(15,23,42,.06);
  box-shadow:0 18px 45px -36px rgba(4,120,87,.35);
  backdrop-filter:blur(10px);
}

.sidebar-section-label{
  display:flex;
  align-items:center;
  gap:.45rem;
  color:#64748b;
  font-size:.72rem;
  text-transform:uppercase;
  letter-spacing:.08em;
  font-weight:950;
  margin:.4rem .4rem .6rem;
}

.sidebar .nav-link{
  color:#334155;
  border-radius:16px;
  display:flex;
  align-items:center;
  margin-bottom:.45rem;
  transition:all .18s ease;
  font-weight:800;
  padding:.8rem .9rem;
  gap:.75rem;
  border:1px solid transparent;
}

.sidebar .nav-link i{
  font-size:1.1rem;
  min-width:20px;
}

.sidebar .nav-link:hover{
  background:rgba(16,185,129,.10);
  color:#047857;
  transform:translateX(4px);
  border-color:rgba(16,185,129,.14);
  box-shadow:0 10px 24px -20px rgba(4,120,87,.55);
}

.sidebar .nav-link.active{
  background:linear-gradient(135deg, #047857, #10b981);
  color:#fff;
  box-shadow:0 14px 30px -18px rgba(4,120,87,.75);
}

.sidebar .step-num{
  display:inline-flex;
  width:30px;
  height:30px;
  border-radius:50%;
  background:linear-gradient(135deg, #d1fae5, #f0fdf4);
  color:#065f46;
  align-items:center;
  justify-content:center;
  font-weight:950;
  font-size:.8rem;
  flex-shrink:0;
  border:1px solid rgba(15,23,42,.06);
}

.sidebar .nav-link.active .step-num{
  background:rgba(255,255,255,.24);
  color:#fff;
  border-color:rgba(255,255,255,.28);
}

.sidebar-divider{
  height:1px;
  background:rgba(15,23,42,.08);
  margin:1rem .35rem;
}

.sidebar-mini-note{
  margin-top:1rem;
  padding:.85rem;
  border-radius:18px;
  background:linear-gradient(135deg, rgba(16,185,129,.09), rgba(20,184,166,.08));
  border:1px solid rgba(16,185,129,.14);
  color:#475569;
  font-size:.78rem;
  line-height:1.45;
}

/* =========================
   MAIN AREA
========================= */

.main-area{
  min-height:calc(100vh - var(--topbar-height));
  flex-grow:1;
  padding:1.5rem;
  min-width:0;
}

/* =========================
   GLOBAL UI STYLES
========================= */

.metric-card{
  border:1px solid rgba(15,23,42,.07);
  border-radius:22px;
  box-shadow:0 18px 50px -34px rgba(4,120,87,.35);
  background:linear-gradient(180deg, #fff, #fbfffd);
  transition:all .2s ease;
  overflow:hidden;
}

.metric-card:hover{
  box-shadow:0 24px 60px -36px rgba(4,120,87,.42);
  transform:translateY(-3px);
}

.metric-card .metric-value{
  font-size:2.2rem;
  font-weight:950;
  letter-spacing:-1px;
  background:linear-gradient(135deg, #047857, #10b981);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}

.metric-card .metric-label{
  color:var(--muted);
  font-size:.75rem;
  text-transform:uppercase;
  letter-spacing:.08em;
  font-weight:850;
}

.btn{
  border-radius:13px;
  font-weight:800;
  transition:all .18s ease;
  padding:.6rem max(.8rem, 1.2rem);
}

.btn:hover{
  transform:translateY(-2px);
}

.btn-primary{
  background:linear-gradient(135deg, #047857, #10b981);
  border:0;
  box-shadow:0 12px 30px -18px rgba(4,120,87,.70);
}

.btn-primary:hover{
  background:linear-gradient(135deg, #065f46, #059669);
  box-shadow:0 16px 36px -20px rgba(4,120,87,.85);
}

.btn-outline-primary{
  color:#047857;
  border-color:rgba(4,120,87,.45);
}

.btn-outline-primary:hover{
  background:linear-gradient(135deg, #047857, #10b981);
  border-color:#047857;
  color:#fff;
}

.card{
  border-radius:18px;
  border:1px solid rgba(15,23,42,.08);
}

.table-preview{
  font-size:.85rem;
}

.table-preview thead th{
  background:linear-gradient(135deg, #f8fafc, #ecfdf5);
  position:sticky;
  top:0;
  font-weight:850;
  color:var(--ink);
  border-color:#d1fae5;
}

.table-preview tbody tr:hover{
  background:#f0fdf4;
}

.diff-changed{
  background:#fff7ed;
  border-left:4px solid var(--warning);
}

.diff-removed{
  background:#f8d7da;
  text-decoration:line-through;
  border-left:4px solid var(--danger);
  color:#721c24;
}

.badge-type{
  font-weight:800;
  padding:.4rem .8rem;
}

.scroll-x{
  overflow-x:auto;
  max-height:480px;
  border-radius:14px;
  border:1px solid rgba(15,23,42,.08);
}

.nav-tabs{
  border-bottom:1px solid rgba(15,23,42,.08);
}

.nav-tabs .nav-link{
  color:var(--muted);
  border:0;
  border-bottom:3px solid transparent;
  font-weight:850;
  transition:all .18s ease;
}

.nav-tabs .nav-link:hover{
  color:var(--brand-dark);
  border-bottom-color:rgba(16,185,129,.18);
}

.nav-tabs .nav-link.active{
  background:rgba(16,185,129,.09);
  color:var(--brand-dark);
  border-bottom-color:var(--brand-dark);
  box-shadow:none;
}

h1,h2,h3,h4,h5,h6{
  color:var(--ink);
  font-weight:850;
  letter-spacing:-.5px;
}

.lead{
  font-size:1.15rem;
  font-weight:500;
}

.form-label{
  font-weight:800;
  color:var(--ink);
}

.form-control,
.form-select{
  border-radius:13px;
  border:1px solid rgba(15,23,42,.12);
  transition:all .18s ease;
}

.form-control:focus,
.form-select:focus{
  border-color:#10b981;
  box-shadow:0 0 0 .25rem rgba(16,185,129,.16);
}

.badge{
  padding:.5rem .875rem;
  font-weight:800;
  border-radius:999px;
  font-size:.82rem;
}

.text-bg-primary{
  background:linear-gradient(135deg, #047857, #10b981) !important;
}

.alert{
  border-radius:18px;
  border:0;
  padding:1.1rem 1.25rem;
  font-weight:550;
  box-shadow:0 18px 45px -36px rgba(15,23,42,.35);
}

.alert-danger{
  background:#f8d7da;
  color:#721c24;
}

.alert-warning{
  background:#fffbeb;
  color:#92400e;
}

.auto-dismiss{
  animation:slideOut 4s ease-in-out forwards;
}

@keyframes slideOut{
  0%{ opacity:1; transform:translateY(0); }
  95%{ opacity:1; transform:translateY(0); }
  100%{ opacity:0; transform:translateY(-20px); }
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 992px){
  .modern-topbar{
    min-height:auto;
    align-items:flex-start;
    flex-direction:column;
    gap:.9rem;
  }

  .topbar-actions{
    width:100%;
  }

  .user-chip{
    flex:1;
  }

  .topbar-btn{
    justify-content:center;
  }

  .app-layout{
    flex-direction:column;
  }

  .sidebar{
    width:100%;
    min-height:auto;
    position:static;
    border-right:0;
    border-bottom:1px solid rgba(15,23,42,.08);
  }

  .sidebar-panel{
    padding:.75rem;
  }

  .sidebar .nav-link{
    margin-bottom:.4rem;
  }

  .main-area{
    padding:1rem;
  }
}

@media (max-width: 576px){
  .topbar-title span{
    display:none;
  }

  .topbar-actions{
    flex-direction:column;
    align-items:stretch;
  }

  .user-chip,
  .topbar-btn{
    width:100%;
  }

  .metric-card .metric-value{
    font-size:1.8rem;
  }

  h1{
    font-size:1.8rem;
  }
}
</style>
</head>

<body>

<header class="modern-topbar">
  <a class="topbar-brand" href="home.php">
    <div class="topbar-logo">
      <i class="bi bi-database-check"></i>
    </div>

    <div>
      <div class="topbar-title">
        <strong>CleanSight</strong>
        <span>— Data Cleaning &amp; Analytics</span>
      </div>
      <div class="topbar-subtitle">
        Upload, profile, clean, analyze, and visualize datasets
      </div>
    </div>
  </a>

  <div class="topbar-actions">
    <div class="user-chip">
      <div class="user-avatar">
        <?= strtoupper(substr($username, 0, 1)) ?>
      </div>

      <div>
        <div class="user-chip-name">
          <?= htmlspecialchars($username) ?>
        </div>
        <div class="user-chip-role">
          <?= htmlspecialchars($role) ?> mode
        </div>
      </div>
    </div>

    <a href="index.php" class="topbar-btn">
      <i class="bi bi-stars"></i>
      Landing
    </a>

    <a href="upload.php" class="topbar-btn topbar-btn-primary">
      <i class="bi bi-cloud-arrow-up"></i>
      Upload
    </a>
  </div>
</header>

<div class="app-layout">

  <aside class="sidebar">
    <div class="sidebar-panel">

      <div class="sidebar-section-label">
        <i class="bi bi-grid"></i>
        Main
      </div>

      <ul class="nav flex-column nav-pills mb-2">
        <li class="nav-item">
          <a class="nav-link <?= nav_active('home.php', $current) ?>" href="home.php">
            <i class="bi bi-house-door"></i>
            Home
          </a>
        </li>
      </ul>

      <div class="sidebar-divider"></div>

      <div class="sidebar-section-label">
        <i class="bi bi-diagram-3"></i>
        Data Workflow
      </div>

      <ul class="nav flex-column nav-pills">
        <li class="nav-item">
          <a class="nav-link <?= nav_active('upload.php', $current) ?>" href="upload.php">
            <span class="step-num">1</span>
            Upload Dataset
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= nav_active('profile.php', $current) ?>" href="profile.php">
            <span class="step-num">2</span>
            Data Profile
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= nav_active('clean.php', $current) ?>" href="clean.php">
            <span class="step-num">3</span>
            Clean Data
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= nav_active('analyze.php', $current) ?>" href="analyze.php">
            <span class="step-num">4</span>
            Analyze Results
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= nav_active('dashboard.php', $current) ?>" href="dashboard.php">
            <span class="step-num">5</span>
            Visualize
          </a>
        </li>
      </ul>

      <div class="sidebar-mini-note">
        <strong>Workflow reminder:</strong><br>
        Upload a CSV file first before moving to Profile, Clean, Analyze, and Visualize.
      </div>

    </div>
  </aside>

  <main class="flex-grow-1 main-area">
    <?php
    if (!empty($_SESSION['workflow_message'])) {
        echo '<div class="alert alert-warning alert-dismissible fade show auto-dismiss" role="alert">';
        echo '<i class="bi bi-exclamation-triangle me-2"></i>';
        echo htmlspecialchars($_SESSION['workflow_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['workflow_message']);
    }
    ?>