<?php
/**
 * User Login Authentication
 */
$pageTitle = 'Sign In - NovaStore';
require_once __DIR__ . '/config/config.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
    } else {
        header('Location: ' . BASE_URL . '/account.php');
    }
    exit;
}

$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, role, name, email, password_hash FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Migrate guest cart items to user
            migrate_session_cart_to_user((int)$user['id']);

            set_flash('success', 'Welcome back, ' . $user['name'] . '!');

            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/index.php');
                exit;
            }

            $redirect = $_SESSION['redirect_after_login'] ?? (BASE_URL . '/account.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = 'Invalid email address or password combination.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-16 sm:py-24">
    <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-10 shadow-xl space-y-6">
        
        <div class="text-center space-y-1">
            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto text-xl mb-3 shadow-inner">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Sign In to Your Account</h1>
            <p class="text-xs text-slate-400">Enter your credentials below to access your account</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs space-y-1">
                <?php foreach ($errors as $err): ?>
                    <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Demo Quick Login Buttons -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider text-center">Quick Demo Accounts (1-Click Fill)</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="fillDemo('admin@ecommerce.com', 'admin123')" class="px-2.5 py-2 rounded-xl bg-purple-100 hover:bg-purple-200 text-purple-800 text-xs font-bold transition-colors">
                    Admin Demo
                </button>
                <button type="button" onclick="fillDemo('customer@ecommerce.com', 'customer123')" class="px-2.5 py-2 rounded-xl bg-brand-100 hover:bg-brand-200 text-brand-800 text-xs font-bold transition-colors">
                    Customer Demo
                </button>
            </div>
        </div>

        <form action="<?= BASE_URL ?>/login.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email Address</label>
                <div class="relative">
                    <input type="email" name="email" id="login-email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="alex@example.com" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                    <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
                    <a href="#" onclick="alert('In this demo environment, passwords can be filled via the 1-Click Demo buttons above.'); return false;" class="text-xs font-semibold text-brand-600 hover:underline">Forgot?</a>
                </div>
                <div class="relative">
                    <input type="password" name="password" id="login-password" required placeholder="••••••••" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-xl shadow-brand-600/20 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                <span>Sign In</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="text-center pt-2 text-xs text-slate-500">
            Don't have an account? 
            <a href="<?= BASE_URL ?>/register.php" class="font-bold text-brand-600 hover:underline">Create an account</a>
        </div>

    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('login-email').value = email;
    document.getElementById('login-password').value = pass;
    showToast('Credentials filled for ' + email, 'info');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
