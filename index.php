<?php
require_once 'db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('menu.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = sanitize($_POST['employee_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'cashier');
    
    if (empty($employee_id) || empty($password)) {
        $error = 'Please enter your Employee ID and Password';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, employee_id, username, password, full_name, role, status FROM users WHERE employee_id = ? AND role = ? AND status = 'active'");
            $stmt->execute([$employee_id, $role]);
            $user = $stmt->fetch();
            
            if ($user && $password === $user['password']) {
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect to menu
                redirect('menu.php');
            } else {
                $error = 'Invalid Employee ID or Password';
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yellow Hauz POS - Login</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,500&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        brand: {
                            DEFAULT: '#FBBF24',
                            light: '#FEF9C3',
                            dark: '#D97706',
                            black: '#171717',
                        },
                        vintage: {
                            paper: '#F5F4F0',
                            border: '#E5E5E5'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #E5E5E5; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #A3A3A3; }
    </style>
</head>
<body class="bg-[#EAE8E3] h-screen w-screen p-3 font-sans text-brand-black overflow-hidden">

    <div class="bg-vintage-paper w-full h-full rounded-2xl shadow-2xl flex overflow-hidden border border-gray-300 relative">
        
        <!-- LEFT SIDE (IMAGE & BRANDING) -->
        <div class="hidden lg:flex w-1/2 relative bg-brand-black items-center justify-center overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=1200&q=80" alt="Vintage Coffee Shop" class="w-full h-full object-cover opacity-40">
                <div class="absolute inset-0 bg-gradient-to-t from-brand-black via-brand-black/80 to-transparent"></div>
            </div>

            <div class="relative z-10 flex flex-col items-center justify-center text-center p-12">
                <div class="w-24 h-24 border-4 border-brand rounded-full flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(251,191,36,0.3)]">
                    <i class="fa-solid fa-mug-hot text-4xl text-brand"></i>
                </div>
                <span class="font-serif italic text-xl text-brand-light mb-2">Coffee at</span>
                <h1 class="font-serif font-bold text-5xl md:text-6xl leading-none text-white tracking-tight uppercase shadow-black drop-shadow-md">Yellow Hauz</h1>
                <div class="flex items-center gap-4 mt-6 w-full max-w-[200px]">
                    <div class="h-px flex-1 bg-brand"></div>
                    <span class="text-xs tracking-[0.3em] text-brand uppercase font-bold">Since 2007</span>
                    <div class="h-px flex-1 bg-brand"></div>
                </div>
                <p class="text-gray-400 mt-8 max-w-sm text-sm leading-relaxed font-medium">
                    Point of Sale System.<br>Manage your orders, tables, and daily operations seamlessly.
                </p>
            </div>
        </div>

        <!-- RIGHT SIDE (LOGIN FORM) -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-8 sm:p-12 relative bg-vintage-paper">
            
            <!-- Mobile Logo (Hidden on Desktop) -->
            <div class="flex lg:hidden flex-col items-center justify-center mb-10 text-center">
                <span class="font-serif italic text-sm text-gray-500 mb-1">Coffee at</span>
                <h1 class="font-serif font-bold text-3xl leading-none text-brand-black tracking-tight uppercase">Yellow Hauz</h1>
                <div class="flex items-center gap-2 mt-2">
                    <div class="h-px w-4 bg-brand"></div>
                    <span class="text-[10px] tracking-[0.2em] text-gray-400 uppercase font-semibold">Since 2007</span>
                    <div class="h-px w-4 bg-brand"></div>
                </div>
            </div>

            <!-- Form Wrapper -->
            <div class="w-full max-w-[420px]">
                
                <!-- Heading -->
                <div class="mb-8 text-center lg:text-left">
                    <h2 class="text-3xl font-serif font-bold text-brand-black mb-2">Welcome Back</h2>
                    <p class="text-gray-500 text-sm font-medium">Please sign in to access the POS system.</p>
                </div>

                <!-- Error/Success Messages -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-100 border border-red-300 rounded-xl text-red-700 text-sm font-medium">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-100 border border-green-300 rounded-xl text-green-700 text-sm font-medium">
                    <i class="fa-solid fa-circle-check mr-2"></i><?php echo $success; ?>
                </div>
                <?php endif; ?>

                <!-- FORM -->
                <form action="index.php" method="POST" class="space-y-6">
                    
                    <!-- Hidden input to store selected role -->
                    <input type="hidden" id="role-input" name="role" value="cashier">

                    <!-- ROLE TOGGLE -->
                    <div class="bg-white p-1.5 rounded-xl flex items-center justify-between border border-gray-200 shadow-sm relative">
                        <button type="button" id="btn-cashier" onclick="setRole('cashier')" class="flex-1 py-3 bg-brand-light rounded-lg shadow-sm border border-brand/30 text-brand-dark font-bold flex items-center justify-center gap-2 transition-all relative z-10">
                            <i class="fa-solid fa-cash-register"></i> Cashier
                        </button>
                        <button type="button" id="btn-admin" onclick="setRole('admin')" class="flex-1 py-3 text-gray-500 hover:text-brand-black font-bold flex items-center justify-center gap-2 transition-all border border-transparent relative z-10">
                            <i class="fa-solid fa-user-tie"></i> Admin
                        </button>
                    </div>

                    <!-- Employee ID / Username -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Employee ID / Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="employee_id" placeholder="Enter your ID" required
                                class="w-full bg-white border border-gray-200 rounded-xl pl-11 pr-4 py-3.5 text-sm font-bold text-brand-black focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all shadow-sm">
                        </div>
                    </div>

                    <!-- Password / PIN -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Password / PIN</label>
                            <a href="#" class="text-xs font-bold text-brand hover:text-brand-dark transition-colors">Forgot PIN?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-solid fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" placeholder="••••••••" required
                                class="w-full bg-white border border-gray-200 rounded-xl pl-11 pr-12 py-3.5 text-sm font-bold text-brand-black focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all shadow-sm tracking-[0.2em]">
                            <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-brand-black transition-colors" onclick="togglePassword()">
                                <i class="fa-solid fa-eye-slash" id="password-toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember-me" type="checkbox" class="w-4 h-4 text-brand-black bg-white border-gray-300 rounded focus:ring-brand-black accent-brand-black cursor-pointer">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-600 font-medium cursor-pointer">Remember me on this terminal</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-brand-black text-brand py-4 rounded-xl font-bold text-lg shadow-[4px_4px_0px_0px_rgba(251,191,36,1)] hover:bg-gray-800 transition-all active:translate-y-1 active:translate-x-1 active:shadow-none border border-transparent uppercase tracking-widest mt-4 flex items-center justify-center gap-3">
                        Sign In <i class="fa-solid fa-arrow-right"></i>
                    </button>

                </form>
                
                <!-- Footer Note -->
                <p class="text-center text-xs font-medium text-gray-400 mt-10">
                    &copy; 2024 Coffee at Yellow Hauz.<br>System Access is restricted to authorized personnel only.
                </p>

            </div>
        </div>
    </div>

    <!-- JavaScript for Role Toggle Interaction -->
    <script>
        function setRole(role) {
            const adminBtn = document.getElementById('btn-admin');
            const cashierBtn = document.getElementById('btn-cashier');
            const roleInput = document.getElementById('role-input');

            roleInput.value = role;

            if(role === 'admin') {
                adminBtn.className = "flex-1 py-3 bg-brand-light rounded-lg shadow-sm border border-brand/30 text-brand-dark font-bold flex items-center justify-center gap-2 transition-all relative z-10";
                cashierBtn.className = "flex-1 py-3 text-gray-500 hover:text-brand-black font-bold flex items-center justify-center gap-2 transition-all border border-transparent relative z-10";
            } else {
                cashierBtn.className = "flex-1 py-3 bg-brand-light rounded-lg shadow-sm border border-brand/30 text-brand-dark font-bold flex items-center justify-center gap-2 transition-all relative z-10";
                adminBtn.className = "flex-1 py-3 text-gray-500 hover:text-brand-black font-bold flex items-center justify-center gap-2 transition-all border border-transparent relative z-10";
            }
        }

        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>
</html>
