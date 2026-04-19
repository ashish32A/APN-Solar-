<?php
session_start();

require_once '../../../../config/database.php';
require_once '../../../../app/Helpers/FlashHelper.php';
require_once '../../../../app/Controllers/AuthController.php';

$controller = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $controller->login();
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AROGYA Solar Power | Sign In</title>
  <meta name="description" content="AROGYA Solar Power Admin Login">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --primary: #f59e0b;
      --primary-dark: #d97706;
      --primary-light: #fef3c7;
      --green: #16a34a;
      --green-light: #dcfce7;
      --bg: #f0fdf4;
      --bg2: #ffffff;
      --border: #e2e8f0;
      --border-focus: #f59e0b;
      --text: #1e293b;
      --text-muted: #64748b;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, .07), 0 2px 4px -2px rgba(0, 0, 0, .05);
      --shadow-lg: 0 20px 60px rgba(0, 0, 0, .10), 0 8px 24px rgba(0, 0, 0, .06);
    }

    html,
    body {
      min-height: 100vh;
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    /* ── Background decoration ── */
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse 70% 50% at 10% 0%, rgba(245, 158, 11, .12) 0%, transparent 60%),
        radial-gradient(ellipse 60% 55% at 90% 100%, rgba(22, 163, 74, .10) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 50% 50%, rgba(255, 255, 255, .6) 0%, transparent 80%);
      pointer-events: none;
      z-index: 0;
    }

    /* Floating sun orb */
    .orb {
      position: fixed;
      border-radius: 50%;
      pointer-events: none;
      z-index: 0;
    }

    .orb-1 {
      width: 380px;
      height: 380px;
      top: -120px;
      right: -80px;
      background: radial-gradient(circle, rgba(245, 158, 11, .22) 0%, transparent 70%);
      animation: float1 8s ease-in-out infinite alternate;
    }

    .orb-2 {
      width: 280px;
      height: 280px;
      bottom: -80px;
      left: -60px;
      background: radial-gradient(circle, rgba(22, 163, 74, .15) 0%, transparent 70%);
      animation: float2 10s ease-in-out infinite alternate;
    }

    @keyframes float1 {
      from {
        transform: scale(1) translate(0, 0);
      }

      to {
        transform: scale(1.15) translate(-20px, 20px);
      }
    }

    @keyframes float2 {
      from {
        transform: scale(1) translate(0, 0);
      }

      to {
        transform: scale(1.1) translate(20px, -20px);
      }
    }

    /* ── Card ── */
    .card {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 460px;
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 44px 48px 40px;
      box-shadow: var(--shadow-lg);
      animation: slide-up .45s cubic-bezier(.22, 1, .36, 1) both;
      margin: 24px;
    }

    @keyframes slide-up {
      from {
        opacity: 0;
        transform: translateY(28px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ── Brand / Logo ── */
    .brand {
      text-align: center;
      margin-bottom: 36px;
    }

    .brand-logo {
      width: 88px;
      height: 88px;
      border-radius: 20px;
      overflow: hidden;
      margin: 0 auto 18px;
      background: #fff;
      border: 2px solid var(--primary-light);
      box-shadow: 0 8px 24px rgba(245, 158, 11, .18), 0 2px 8px rgba(0, 0, 0, .06);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform .3s, box-shadow .3s;
    }

    .brand-logo:hover {
      transform: scale(1.05);
      box-shadow: 0 12px 32px rgba(245, 158, 11, .28);
    }

    .brand-logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      padding: 2px;
    }

    .brand h1 {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -.025em;
      line-height: 1.2;
    }

    .brand h1 span {
      color: var(--primary);
    }

    .brand p {
      font-size: .875rem;
      color: var(--text-muted);
      margin-top: 6px;
      font-weight: 400;
    }

    /* ── Divider ── */
    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 0 0 28px;
    }

    /* ── Flash Alert ── */
    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: .875rem;
      font-weight: 500;
      margin-bottom: 20px;
    }

    .alert-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #dc2626;
    }

    .alert-success {
      background: var(--green-light);
      border: 1px solid #bbf7d0;
      color: var(--green);
    }

    /* ── Form ── */
    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-size: .78rem;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 7px;
      letter-spacing: .05em;
      text-transform: uppercase;
    }

    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      color: #cbd5e1;
      font-size: .9rem;
      pointer-events: none;
      transition: color .2s;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px 14px 12px 42px;
      background: #f8fafc;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-size: .95rem;
      font-family: inherit;
      transition: border-color .2s, box-shadow .2s, background .2s;
      outline: none;
    }

    input[type="email"]:hover,
    input[type="password"]:hover {
      border-color: #cbd5e1;
      background: #fff;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: var(--primary);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(245, 158, 11, .12);
    }

    .input-wrap:focus-within .input-icon {
      color: var(--primary);
    }

    /* ── Remember me row ── */
    .form-row {
      display: flex;
      align-items: center;
      margin-bottom: 24px;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      font-size: .875rem;
      color: var(--text-muted);
      font-weight: 500;
      user-select: none;
    }

    .remember input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--primary);
      cursor: pointer;
      border-radius: 4px;
    }

    /* ── Submit Button ── */
    .btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      letter-spacing: .02em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: transform .15s, box-shadow .2s, opacity .15s;
      box-shadow: 0 4px 14px rgba(245, 158, 11, .35);
      position: relative;
      overflow: hidden;
    }

    .btn-login::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255, 255, 255, .15) 0%, transparent 60%);
      border-radius: inherit;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(245, 158, 11, .45);
    }

    .btn-login:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(245, 158, 11, .3);
    }

    .btn-login:disabled {
      opacity: .75;
      cursor: not-allowed;
      transform: none;
    }

    /* ── Card footer ── */
    .card-footer {
      text-align: center;
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }

    .card-footer a {
      font-size: .875rem;
      color: var(--primary-dark);
      text-decoration: none;
      font-weight: 600;
      transition: color .2s;
    }

    .card-footer a:hover {
      color: var(--primary);
    }

    /* ── Footer ── */
    .powered {
      position: fixed;
      bottom: 18px;
      width: 100%;
      text-align: center;
      font-size: .72rem;
      color: var(--text-muted);
      z-index: 10;
    }

    /* ── Responsive ── */
    @media (max-width: 520px) {
      .card {
        padding: 32px 24px 28px;
        margin: 16px;
      }

      .brand h1 {
        font-size: 1.35rem;
      }
    }
  </style>
</head>

<body>

  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>

  <div class="card">

    <!-- Brand -->
    <div class="brand">
      <div class="brand-logo">
        <img src="/APN-Solar/assets/images/Logo.png" alt="AROGYA Solar Power Logo">
      </div>
      <h1><span>AROGYA</span> Solar Power</h1>
      <p>Sign in to your admin account</p>
    </div>

    <hr class="divider">

    <!-- Flash Message -->
    <?php if ($flash): ?>
      <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'error' : 'success'; ?>">
        <i class="fas fa-<?php echo $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
        <?php echo htmlspecialchars($flash['message']); ?>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form action="" method="POST" id="loginForm" autocomplete="on">

      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <i class="fas fa-envelope input-icon"></i>
          <input type="email" id="email" name="email" placeholder="abc@gmail.com"
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
      </div>

      <div class="form-row">
        <label class="remember">
          <input type="checkbox" id="remember" name="remember" value="1">
          Remember Me
        </label>
      </div>

      <button type="submit" class="btn-login" id="loginBtn">
        <i class="fas fa-sign-in-alt"></i>
        Sign In
      </button>

    </form>

    <!-- Footer link -->
    <div class="card-footer">
      <a href="#" id="registerLink">
        <i class="fas fa-user-plus" style="margin-right:4px;font-size:.8rem;"></i>
        Register a new membership
      </a>
    </div>

  </div>

  <p class="powered">AROGYA Solar Power &copy; <?php echo date('Y'); ?></p>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function () {
      const btn = document.getElementById('loginBtn');
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
      btn.disabled = true;
    });
  </script>

</body>

</html>