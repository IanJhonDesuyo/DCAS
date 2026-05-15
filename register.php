<?php
session_start();
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $error = 'Username or email is already registered.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $insert = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $insert->bind_param("ssss", $username, $email, $password_hash, $role);

            if ($insert->execute()) {
                $success = 'Account created successfully. You may now sign in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Account · Data Cleaning &amp; Analytics System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  :root{
    --brand-1:#6366f1; --brand-2:#0d6efd; --brand-3:#22d3ee;
    --ink:#0f172a; --ink-soft:#475569;
  }

  html,body{ height:100%; }

  body{
    margin:0;
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
    color:var(--ink);
    background:#f6f8ff;
    overflow-x:hidden;
  }

  .auth-shell{
    min-height:100vh;
    display:grid;
    grid-template-columns: 1.05fr 1fr;
    position:relative;
    isolation:isolate;
  }

  .auth-shell::before{
    content:""; position:absolute; inset:0; z-index:-1;
    background:
      radial-gradient(700px 360px at 10% 15%, rgba(99,102,241,.18), transparent 60%),
      radial-gradient(600px 320px at 90% 85%, rgba(34,211,238,.18), transparent 60%),
      radial-gradient(500px 260px at 50% 50%, rgba(13,110,253,.10), transparent 60%);
  }

  .brand-side{
    position:relative;
    padding:3rem;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    background:linear-gradient(135deg, #0b1437 0%, #1e3a8a 50%, #0d6efd 100%);
    color:#fff;
    overflow:hidden;
  }

  .brand-side::before{
    content:"";
    position:absolute;
    right:-160px;
    top:-160px;
    width:480px;
    height:480px;
    border-radius:50%;
    background:conic-gradient(from 120deg, var(--brand-1), var(--brand-3), var(--brand-2), var(--brand-1));
    filter:blur(70px);
    opacity:.55;
  }

  .brand-side::after{
    content:"";
    position:absolute;
    left:-120px;
    bottom:-120px;
    width:360px;
    height:360px;
    border-radius:50%;
    background:radial-gradient(circle, rgba(34,211,238,.5), transparent 70%);
    filter:blur(40px);
  }

  .brand-side > *{
    position:relative;
    z-index:1;
  }

  .brand-logo{
    display:inline-flex;
    align-items:center;
    gap:.6rem;
    font-weight:700;
    font-size:1.05rem;
    letter-spacing:.02em;
  }

  .brand-logo .logo-dot{
    width:36px;
    height:36px;
    border-radius:10px;
    background:linear-gradient(135deg, #fff, #c7d2fe);
    color:#0b1437;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 8px 24px -8px rgba(255,255,255,.4);
  }

  .brand-headline{
    margin-top:auto;
    font-size:2.6rem;
    font-weight:800;
    line-height:1.05;
    letter-spacing:-1px;
  }

  .brand-headline .grad{
    background:linear-gradient(90deg,#fff,#c7d2fe 60%, #67e8f9);
    -webkit-background-clip:text;
    background-clip:text;
    color:transparent;
  }

  .brand-sub{
    color:rgba(255,255,255,.75);
    max-width:460px;
    margin-top:1rem;
    font-size:1.05rem;
  }

  .brand-features{
    margin-top:2rem;
    display:grid;
    gap:.75rem;
    max-width:460px;
  }

  .brand-feature{
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.7rem .9rem;
    border-radius:14px;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    font-weight:500;
  }

  .brand-feature i{
    width:32px;
    height:32px;
    border-radius:8px;
    background:rgba(255,255,255,.15);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    color:#a5f3fc;
  }

  .brand-foot{
    margin-top:auto;
    padding-top:2rem;
    color:rgba(255,255,255,.55);
    font-size:.85rem;
  }

  .form-side{
    display:flex;
    align-items:center;
    justify-content:center;
    padding:3rem 2rem;
  }

  .auth-card{
    width:100%;
    max-width:460px;
    background:rgba(255,255,255,.85);
    border:1px solid rgba(15,23,42,.06);
    border-radius:24px;
    padding:2.5rem;
    box-shadow:0 30px 80px -40px rgba(13,110,253,.35), 0 8px 24px -12px rgba(15,23,42,.08);
    backdrop-filter: blur(12px);
  }

  .eyebrow{
    display:inline-flex;
    align-items:center;
    gap:.4rem;
    padding:.35rem .75rem;
    border-radius:999px;
    background:rgba(99,102,241,.1);
    color:#4f46e5;
    font-weight:600;
    font-size:.75rem;
    letter-spacing:.06em;
    text-transform:uppercase;
    border:1px solid rgba(99,102,241,.2);
  }

  .auth-card h1{
    font-weight:800;
    font-size:2rem;
    margin:.9rem 0 .35rem;
    background:linear-gradient(120deg,#0f172a,#1e3a8a 50%, #0d6efd);
    -webkit-background-clip:text;
    background-clip:text;
    color:transparent;
  }

  .auth-card .subtitle{
    color:var(--ink-soft);
    margin-bottom:1.75rem;
  }

  .field{
    position:relative;
    margin-bottom:1rem;
  }

  .field .form-control{
    height:52px;
    padding-left:2.75rem;
    border-radius:12px;
    border:1px solid rgba(15,23,42,.12);
    background:#fff;
    font-size:.97rem;
    transition:border-color .15s ease, box-shadow .15s ease;
  }

  .field .form-control:focus{
    border-color:#6366f1;
    box-shadow:0 0 0 4px rgba(99,102,241,.15);
  }

  .field .field-icon{
    position:absolute;
    left:1rem;
    top:50%;
    transform:translateY(-50%);
    color:#94a3b8;
    font-size:1.05rem;
  }

  .field .toggle-pass{
    position:absolute;
    right:.75rem;
    top:50%;
    transform:translateY(-50%);
    background:none;
    border:none;
    color:#94a3b8;
    padding:.25rem;
  }

  .field .toggle-pass:hover{
    color:#475569;
  }

  .btn-hero{
    width:100%;
    height:52px;
    border-radius:12px;
    background:linear-gradient(135deg, var(--brand-2), var(--brand-1));
    border:none;
    color:#fff;
    font-weight:600;
    font-size:1rem;
    box-shadow:0 12px 30px -12px rgba(13,110,253,.6);
    transition:transform .15s ease, filter .15s ease;
  }

  .btn-hero:hover{
    transform:translateY(-1px);
    filter:brightness(1.05);
    color:#fff;
  }

  .divider{
    display:flex;
    align-items:center;
    gap:.75rem;
    margin:1.5rem 0;
    color:#94a3b8;
    font-size:.8rem;
    text-transform:uppercase;
    letter-spacing:.08em;
  }

  .divider::before,
  .divider::after{
    content:"";
    flex:1;
    height:1px;
    background:rgba(15,23,42,.08);
  }

  .signin-cta{
    text-align:center;
    color:var(--ink-soft);
    font-size:.95rem;
  }

  .signin-cta a{
    color:#0d6efd;
    font-weight:700;
    text-decoration:none;
  }

  .signin-cta a:hover{
    text-decoration:underline;
  }

  .alert-soft{
    border-radius:12px;
    padding:.75rem 1rem;
    font-size:.9rem;
    margin-bottom:1.25rem;
    display:flex;
    align-items:center;
    gap:.5rem;
  }

  .alert-error{
    background:rgba(220,38,38,.08);
    color:#b91c1c;
    border:1px solid rgba(220,38,38,.2);
  }

  .alert-success{
    background:rgba(22,163,74,.08);
    color:#15803d;
    border:1px solid rgba(22,163,74,.2);
  }

  @media (max-width: 900px){
    .auth-shell{
      grid-template-columns: 1fr;
    }

    .brand-side{
      display:none;
    }

    .form-side{
      padding:2rem 1rem;
    }

    .auth-card{
      padding:2rem;
    }
  }
</style>
</head>

<body>

<div class="auth-shell">

  <!-- LEFT BRAND PANEL -->
  <aside class="brand-side">
    <div class="brand-logo">
      <span class="logo-dot"><i class="bi bi-graph-up-arrow"></i></span>
      DataClean Analytics
    </div>

    <div>
      <h2 class="brand-headline">
        Start your <span class="grad">data cleaning journey</span>.
      </h2>

      <p class="brand-sub">
        Create an account to upload datasets, clean records, generate insights, and visualize your analytics securely.
      </p>

      <div class="brand-features">
        <div class="brand-feature">
          <i class="bi bi-person-check"></i>
          User-based access to uploaded files
        </div>

        <div class="brand-feature">
          <i class="bi bi-database-check"></i>
          Clean and manage your own datasets
        </div>

        <div class="brand-feature">
          <i class="bi bi-bar-chart-line"></i>
          Generate insights and visual reports
        </div>
      </div>
    </div>

    <div class="brand-foot">© <?= date('Y') ?> DataClean Analytics · All rights reserved.</div>
  </aside>

  <!-- RIGHT FORM PANEL -->
  <main class="form-side">
    <div class="auth-card">
      <span class="eyebrow"><i class="bi bi-person-plus"></i> Register</span>

      <h1>Create account</h1>

      <p class="subtitle">
        Fill in your details to access the Data Cleaning &amp; Analytics System.
      </p>

      <?php if ($error): ?>
        <div class="alert-soft alert-error">
          <i class="bi bi-exclamation-circle-fill"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert-soft alert-success">
          <i class="bi bi-check-circle-fill"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="field">
          <i class="bi bi-person field-icon"></i>
          <input
            type="text"
            name="username"
            class="form-control"
            placeholder="Username"
            required
            autofocus
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          >
        </div>

        <div class="field">
          <i class="bi bi-envelope field-icon"></i>
          <input
            type="email"
            name="email"
            class="form-control"
            placeholder="you@company.com"
            required
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          >
        </div>

        <div class="field">
          <i class="bi bi-lock field-icon"></i>
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Create password"
            required
          >

          <button type="button" class="toggle-pass" onclick="togglePass('password', 'eyeIcon')" aria-label="Show password">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>

        <div class="field">
          <i class="bi bi-shield-lock field-icon"></i>
          <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            class="form-control"
            placeholder="Confirm password"
            required
          >

          <button type="button" class="toggle-pass" onclick="togglePass('confirm_password', 'eyeIconConfirm')" aria-label="Show password">
            <i class="bi bi-eye" id="eyeIconConfirm"></i>
          </button>
        </div>

        <button type="submit" class="btn-hero">
          <i class="bi bi-person-plus me-2"></i>Create Account
        </button>

        <div class="divider">or</div>

        <div class="signin-cta">
          Already have an account? <a href="index.php">Sign in</a>
        </div>
      </form>
    </div>
  </main>

</div>

<script>
  function togglePass(inputId, iconId){
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
  }
</script>

</body>
</html>