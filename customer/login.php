<?php
require_once __DIR__ . '/../db.php';
$return = ($_GET['return'] ?? '') === 'reservation' ? 'reservation/' : './';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $mode = $_POST['mode'] ?? 'login';
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) throw new RuntimeException('Enter a valid email and a password with at least 8 characters.');
        if ($mode === 'register') {
            $name = sanitize($_POST['full_name'] ?? '');
            $contact = preg_replace('/[^0-9+\-() ]/', '', (string)($_POST['contact_number'] ?? ''));
            if ($name === '' || strlen($name) > 100 || strlen($contact) < 7) throw new RuntimeException('Enter your name and a valid contact number.');
            $stmt = $pdo->prepare('INSERT INTO customer_accounts (full_name, email, contact_number, password) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $contact, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['customer_id'] = (int)$pdo->lastInsertId();
            $_SESSION['customer_name'] = $name;
        } else {
            $stmt = $pdo->prepare("SELECT id, full_name, password FROM customer_accounts WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]); $customer = $stmt->fetch();
            if (!$customer || !password_verify($password, $customer['password'])) throw new RuntimeException('Invalid email or password.');
            $_SESSION['customer_id'] = (int)$customer['id'];
            $_SESSION['customer_name'] = $customer['full_name'];
        }
        redirect($return);
    } catch (PDOException $e) { $error = $e->getCode() === '23000' ? 'An account with that email already exists.' : 'Account service is unavailable.'; }
      catch (Throwable $e) { $error = $e->getMessage(); }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Customer Login | Yellow Hauz</title><script src="https://cdn.tailwindcss.com"></script></head><body class="grid min-h-screen place-items-center bg-[#EAE8E3] p-4"><main class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl"><a href="./" class="text-sm font-bold text-amber-700">← Customer menu</a><p class="mt-6 text-xs font-bold uppercase tracking-widest text-amber-700">Yellow Hauz guest portal</p><h1 class="mt-1 font-serif text-3xl font-bold">Sign in to reserve</h1><p class="mt-2 text-sm text-gray-500">Customer accounts are separate from staff login accounts.</p><?php if($error): ?><p class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><?= escape($error) ?></p><?php endif; ?><div class="mt-6 flex rounded-xl bg-gray-100 p-1"><button class="mode flex-1 rounded-lg bg-white py-2 font-bold" data-mode="login">Sign in</button><button class="mode flex-1 rounded-lg py-2 font-bold text-gray-500" data-mode="register">Create account</button></div><form method="post" class="mt-5 space-y-3"><input type="hidden" id="mode" name="mode" value="login"><?= csrfField() ?><div id="register-fields" class="hidden space-y-3"><input name="full_name" maxlength="100" placeholder="Full name" class="w-full rounded-xl border p-3"><input name="contact_number" maxlength="30" placeholder="Contact number" class="w-full rounded-xl border p-3"></div><input required name="email" type="email" placeholder="Email address" class="w-full rounded-xl border p-3"><input required name="password" type="password" minlength="8" placeholder="Password (at least 8 characters)" class="w-full rounded-xl border p-3"><button class="w-full rounded-xl bg-stone-900 py-3 font-bold text-amber-300">Continue</button></form></main><script>document.querySelectorAll('.mode').forEach(b=>b.onclick=()=>{let register=b.dataset.mode==='register';document.getElementById('mode').value=b.dataset.mode;document.getElementById('register-fields').classList.toggle('hidden',!register);document.querySelectorAll('.mode').forEach(x=>x.className='mode flex-1 rounded-lg py-2 font-bold text-gray-500');b.className='mode flex-1 rounded-lg bg-white py-2 font-bold'});</script></body></html>
