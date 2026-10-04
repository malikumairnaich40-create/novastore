<?php
/**
 * Customer Registration
 */
$pageTitle = 'Create Account - NovaStore';
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/account.php');
    exit;
}

$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid security token. Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $errors[] = 'Your full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email is already taken
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'This email address is already registered. Please sign in instead.';
        }
    }

    // Register user
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $insert = $pdo->prepare("
            INSERT INTO users (role, name, email, password_hash) 
            VALUES ('customer', ?, ?, ?)
        ");
        $insert->execute([$name, $email, $passwordHash]);
        $userId = (int)$pdo->lastInsertId();

        // Auto-login new customer
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'customer';

        // Migrate guest cart
        migrate_session_cart_to_user($userId);

        set_flash('success', 'Your account has been created successfully! Welcome to NovaStore.');
        header('Location: ' . BASE_URL . '/account.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto px-4 py-16 sm:py-24">
    <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-10 shadow-xl space-y-6">
        
        <div class="text-center space-y-1">
            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto text-xl mb-3 shadow-inner">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create an Account</h1>
            <p class="text-xs text-slate-400">Join NovaStore to track orders, save items, and get member deals</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs space-y-1">
                <?php foreach ($errors as $err): ?>
                    <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/register.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Full Name</label>
                <div class="relative">
                    <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="Alex Johnson" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                    <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email Address</label>
                <div class="relative">
                    <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="alex@example.com" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                    <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Password (min. 6 characters)</label>
                <div class="relative">
                    <input type="password" name="password" required placeholder="••••••••" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none">
                    <i class="fa-solid fa-check-double absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-xl shadow-brand-600/20 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                <span>Create Account</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="text-center pt-2 text-xs text-slate-500">
            Already have an account? 
            <a href="<?= BASE_URL ?>/login.php" class="font-bold text-brand-600 hover:underline">Sign In</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
