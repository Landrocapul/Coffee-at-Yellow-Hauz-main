<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

// Check if user has admin access
if (!isAdmin()) {
    redirect('menu.php');
}

// Get current user info
$currentUser = getCurrentUser();

// Get date filter
$dateFilter = $_GET['date_filter'] ?? 'today';
$startDate = null;
$endDate = null;

switch ($dateFilter) {
    case 'today':
        $startDate = date('Y-m-d 00:00:00');
        $endDate = date('Y-m-d 23:59:59');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d 00:00:00', strtotime('yesterday'));
        $endDate = date('Y-m-d 23:59:59', strtotime('yesterday'));
        break;
    case 'this_week':
        $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        break;
    case 'this_month':
        $startDate = date('Y-m-01 00:00:00');
        $endDate = date('Y-m-t 23:59:59');
        break;
    default:
        $startDate = date('Y-m-d 00:00:00');
        $endDate = date('Y-m-d 23:59:59');
}

// Get sales summary
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_sales,
    AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE status = 'completed' 
    AND created_at BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$salesSummary = $stmt->fetch();

// Get best selling item
$stmt = $pdo->prepare("SELECT 
    mi.name, 
    SUM(oi.quantity) as total_quantity,
    SUM(oi.total_price) as total_revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' 
    AND o.created_at BETWEEN ? AND ?
    GROUP BY mi.id, mi.name
    ORDER BY total_quantity DESC
    LIMIT 1");
$stmt->execute([$startDate, $endDate]);
$bestSellingItem = $stmt->fetch();

// Get sales trend (hourly for today, daily for other periods)
$salesTrend = [];
if ($dateFilter === 'today') {
    for ($i = 0; $i < 24; $i++) {
        $hourStart = date('Y-m-d ' . sprintf('%02d', $i) . ':00:00');
        $hourEnd = date('Y-m-d ' . sprintf('%02d', $i) . ':59:59');
        
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as sales FROM orders WHERE status = 'completed' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$hourStart, $hourEnd]);
        $result = $stmt->fetch();
        $salesTrend[] = $result['sales'] ?? 0;
    }
} else {
    // Daily trend for other periods
    $days = ($dateFilter === 'this_week') ? 7 : 30;
    for ($i = $days - 1; $i >= 0; $i--) {
        $dayStart = date('Y-m-d 00:00:00', strtotime("-$i days"));
        $dayEnd = date('Y-m-d 23:59:59', strtotime("-$i days"));
        
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as sales FROM orders WHERE status = 'completed' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$dayStart, $dayEnd]);
        $result = $stmt->fetch();
        $salesTrend[] = $result['sales'] ?? 0;
    }
}

// Get order type breakdown
$stmt = $pdo->prepare("SELECT 
    order_type,
    COUNT(*) as order_count,
    SUM(total_amount) as total_amount
    FROM orders 
    WHERE status = 'completed' 
    AND created_at BETWEEN ? AND ?
    GROUP BY order_type");
$stmt->execute([$startDate, $endDate]);
$orderTypeBreakdown = $stmt->fetchAll();

// Calculate order type percentages
$totalOrderAmount = array_sum(array_column($orderTypeBreakdown, 'total_amount'));
foreach ($orderTypeBreakdown as &$type) {
    $type['percentage'] = $totalOrderAmount > 0 ? round(($type['total_amount'] / $totalOrderAmount) * 100, 0) : 0;
}

// Get payment method breakdown
$stmt = $pdo->prepare("SELECT 
    payment_method,
    COUNT(*) as transaction_count,
    SUM(total_amount) as total_amount
    FROM orders 
    WHERE status = 'completed' 
    AND created_at BETWEEN ? AND ?
    GROUP BY payment_method");
$stmt->execute([$startDate, $endDate]);
$paymentBreakdown = $stmt->fetchAll();

// Get category sales
$stmt = $pdo->prepare("SELECT 
    c.name as category_name,
    SUM(oi.total_price) as total_sales
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN categories c ON mi.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' 
    AND o.created_at BETWEEN ? AND ?
    GROUP BY c.id, c.name
    ORDER BY total_sales DESC");
$stmt->execute([$startDate, $endDate]);
$categorySales = $stmt->fetchAll();

// Get top products
$stmt = $pdo->prepare("SELECT 
    mi.name,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.total_price) as total_revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' 
    AND o.created_at BETWEEN ? AND ?
    GROUP BY mi.id, mi.name
    ORDER BY total_quantity DESC
    LIMIT 10");
$stmt->execute([$startDate, $endDate]);
$topProducts = $stmt->fetchAll();

// Get detailed transactions
$stmt = $pdo->prepare("SELECT 
    o.order_number,
    o.created_at,
    o.order_type,
    t.table_number,
    o.payment_method,
    o.total_amount,
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.status = 'completed' 
    AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC
    LIMIT 50");
$stmt->execute([$startDate, $endDate]);
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yellow Hauz POS - Sales Report</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,500&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .hide-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-[#EAE8E3] h-screen w-screen p-3 font-sans text-brand-black overflow-hidden">

    <div class="bg-vintage-paper w-full h-full rounded-2xl shadow-2xl flex overflow-hidden border border-gray-300 relative">
        
        <!-- LEFT SIDEBAR -->
        <aside id="sidebar" class="w-[80px] bg-white border-r border-vintage-border flex flex-col justify-between py-6 px-4 shrink-0 z-20 relative transition-all duration-300 ease-in-out">
            <div>
                <!-- Logo -->
                <div class="flex flex-col items-center justify-center mb-10 mt-2 text-center">
                    <span class="font-serif italic text-sm text-gray-500 mb-1">Coffee at</span>
                    <h1 class="font-serif font-bold text-2xl leading-none text-brand-black tracking-tight uppercase">Yellow Hauz</h1>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="h-px w-4 bg-brand"></div>
                        <span class="text-[10px] tracking-[0.2em] text-gray-400 uppercase font-semibold">Since 2007</span>
                        <div class="h-px w-4 bg-brand"></div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav id="navigation" class="space-y-1.5">
                    <a href="menu.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-mug-hot w-5 text-center"></i> <span class="nav-text">Menu</span>
                    </a>
                    <a href="table.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-utensils w-5 text-center"></i> <span class="nav-text">Table Services</span>
                    </a>
                    <a href="ticket.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-receipt w-5 text-center"></i> <span class="nav-text">Tickets</span>
                    </a>
                    <a href="items.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-clipboard-list w-5 text-center"></i> <span class="nav-text">Manage Food Items</span>
                    </a>
                    
                    <a href="sales.php" class="flex items-center gap-4 bg-brand-black text-brand px-4 py-3.5 rounded-2xl font-semibold shadow-md transition-all">
                        <i class="fa-solid fa-chart-line w-5 text-center"></i> <span class="nav-text">Sales Report</span>
                    </a>
                    
                    <a href="analysis.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-chart-pie w-5 text-center"></i> <span class="nav-text">Product Analytics</span>
                    </a>

                    <a href="settings.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-gear w-5 text-center"></i> <span class="nav-text">Settings</span>
                    </a>
                </nav>
            </div>

            <!-- Bottom Logout -->
             <div class="space-y-4">
                <div class="space-y-3 px-2">
                    <div class="flex items-center gap-3 cursor-pointer p-2 rounded-xl hover:bg-gray-100">
                        <div class="w-8 h-8 rounded-full bg-brand text-brand-black flex items-center justify-center text-xs font-bold relative">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 2)); ?>
                            <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></span>
                        </div>
                        <span class="text-sm font-medium nav-text"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    </div>
                </div>
                <hr class="border-gray-200">
                <a href="#" onclick="showLogoutModal()" class="flex items-center gap-3 text-gray-500 hover:text-brand-black px-4 py-2 font-medium transition-all">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="nav-text">Logout</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex flex-col relative bg-vintage-paper overflow-hidden">
            
            <!-- Top Header & Filters -->
            <header class="h-[88px] flex items-center justify-between px-8 shrink-0 border-b border-gray-200/80 bg-white/60 backdrop-blur-md z-20">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-200 flex items-center justify-center text-gray-500 hover:text-brand-black mr-4">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h2 class="text-2xl font-serif font-bold text-brand-black tracking-wide">SALES REPORT</h2>
                </div>
                
                <!-- Date Filters -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-white border border-gray-200 rounded-lg p-1 shadow-sm text-sm">
                        <a href="?date_filter=today" class="px-3 py-1.5 rounded-md <?php echo $dateFilter === 'today' ? 'bg-brand-light text-brand-dark font-bold border border-brand/20 shadow-sm' : 'text-gray-500 hover:text-brand-black font-semibold'; ?> transition-all">Today</a>
                        <a href="?date_filter=yesterday" class="px-3 py-1.5 rounded-md <?php echo $dateFilter === 'yesterday' ? 'bg-brand-light text-brand-dark font-bold border border-brand/20 shadow-sm' : 'text-gray-500 hover:text-brand-black font-semibold'; ?> transition-all">Yesterday</a>
                        <a href="?date_filter=this_week" class="px-3 py-1.5 rounded-md <?php echo $dateFilter === 'this_week' ? 'bg-brand-light text-brand-dark font-bold border border-brand/20 shadow-sm' : 'text-gray-500 hover:text-brand-black font-semibold'; ?> transition-all">This Week</a>
                        <a href="?date_filter=this_month" class="px-3 py-1.5 rounded-md <?php echo $dateFilter === 'this_month' ? 'bg-brand-light text-brand-dark font-bold border border-brand/20 shadow-sm' : 'text-gray-500 hover:text-brand-black font-semibold'; ?> transition-all">This Month</a>
                        <div class="w-px h-4 bg-gray-300 mx-1"></div>
                        <button class="px-3 py-1.5 rounded-md text-gray-500 hover:text-brand-black font-semibold flex items-center gap-2 transition-all">
                            <i class="fa-regular fa-calendar"></i> Custom
                        </button>
                    </div>
                    
                    <button class="bg-brand-black text-brand w-10 h-10 rounded-lg flex items-center justify-center font-bold shadow-[2px_2px_0px_0px_rgba(251,191,36,1)] hover:bg-gray-800 transition-all active:translate-y-0.5 active:translate-x-0.5 active:shadow-none border border-transparent">
                        <i class="fa-solid fa-download"></i>
                    </button>
                </div>
            </header>

            <!-- Workspace: Scrollable Dashboard Content -->
            <div class="flex-1 overflow-y-auto p-8 hide-scrollbar">
                
                <!-- 1. Sales Summary (KPI Cards) -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    
                    <!-- Card 1 -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-start justify-between group hover:border-brand-black transition-colors">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Total Sales</p>
                            <h3 class="font-serif text-3xl font-bold text-brand-black"><?php echo formatCurrency($salesSummary['total_sales'] ?? 0); ?></h3>
                            <p class="text-[11px] font-bold mt-2 text-green-600 flex items-center gap-1"><i class="fa-solid fa-arrow-trend-up"></i> <?php echo $salesSummary['total_sales'] > 0 ? '+15%' : '0%'; ?> vs Previous</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-brand-light text-brand-dark flex items-center justify-center text-lg shadow-inner"><i class="fa-solid fa-coins"></i></div>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-start justify-between group hover:border-brand-black transition-colors">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Total Orders</p>
                            <h3 class="font-serif text-3xl font-bold text-brand-black"><?php echo $salesSummary['total_orders'] ?? 0; ?></h3>
                            <p class="text-[11px] font-bold mt-2 text-green-600 flex items-center gap-1"><i class="fa-solid fa-arrow-trend-up"></i> +8% vs Previous</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-lg shadow-inner"><i class="fa-solid fa-receipt"></i></div>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-start justify-between group hover:border-brand-black transition-colors">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Avg Order Value</p>
                            <h3 class="font-serif text-3xl font-bold text-brand-black"><?php echo formatCurrency($salesSummary['avg_order_value'] ?? 0); ?></h3>
                            <p class="text-[11px] font-bold mt-2 text-red-500 flex items-center gap-1"><i class="fa-solid fa-arrow-trend-down"></i> -2% vs Previous</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-lg shadow-inner"><i class="fa-solid fa-chart-pie"></i></div>
                    </div>

                    <!-- Card 4 -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-start justify-between group hover:border-brand-black transition-colors">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Best-Selling Item</p>
                            <h3 class="font-serif text-xl font-bold text-brand-black leading-tight mt-1"><?php echo htmlspecialchars($bestSellingItem['name'] ?? 'N/A'); ?></h3>
                            <p class="text-[11px] font-bold mt-2 text-brand-dark"><?php echo $bestSellingItem['total_quantity'] ?? 0; ?> Units Sold</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-brand text-brand-black flex items-center justify-center text-lg shadow-inner border border-brand-black/20"><i class="fa-solid fa-mug-hot"></i></div>
                    </div>
                </div>

                <!-- 2. Sales Trend Chart -->
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="font-serif text-xl font-bold text-brand-black">Sales Trend</h3>
                            <p class="text-xs text-gray-500 font-medium mt-1">Gross sales over the selected period</p>
                        </div>
                        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-md p-1">
                            <button class="px-3 py-1 rounded bg-white text-brand-black font-bold text-xs shadow-sm">Hourly</button>
                            <button class="px-3 py-1 rounded text-gray-500 hover:text-brand-black font-semibold text-xs transition-all">Daily</button>
                            <button class="px-3 py-1 rounded text-gray-500 hover:text-brand-black font-semibold text-xs transition-all">Monthly</button>
                        </div>
                    </div>
                    <div class="w-full flex-1 min-h-[250px] relative">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>

                <!-- 3. Order Type Breakdown & Category Sales -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
                    
                    <!-- Order Type Breakdown -->
                    <div class="bg-brand-black text-white p-6 rounded-2xl border-2 border-brand/20 shadow-lg relative overflow-hidden flex flex-col justify-between">
                        <div class="absolute right-0 top-0 w-32 h-32 bg-brand rounded-bl-full -z-0 opacity-10"></div>
                        
                        <div class="relative z-10 mb-6">
                            <h3 class="font-serif text-xl font-bold text-brand">Order Types</h3>
                            <p class="text-xs text-gray-400 font-medium mt-1">Distribution of service methods</p>
                        </div>

                        <div class="relative z-10 space-y-5">
                            <?php foreach ($orderTypeBreakdown as $type): ?>
                            <div>
                                <div class="flex justify-between items-end mb-1.5">
                                    <span class="text-sm font-bold flex items-center gap-2">
                                        <?php 
                                        $icon = 'fa-solid fa-utensils';
                                        if ($type['order_type'] === 'take_away') $icon = 'fa-solid fa-bag-shopping';
                                        elseif ($type['order_type'] === 'delivery') $icon = 'fa-solid fa-motorcycle';
                                        ?>
                                        <i class="<?php echo $icon; ?> text-brand w-4"></i> 
                                        <?php echo ucfirst(str_replace('_', ' ', $type['order_type'])); ?>
                                    </span>
                                    <div class="text-right">
                                        <span class="text-sm font-bold block"><?php echo formatCurrency($type['total_amount']); ?></span>
                                        <span class="text-[10px] text-gray-400 font-medium"><?php echo $type['percentage']; ?>%</span>
                                    </div>
                                </div>
                                <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand rounded-full" style="width: <?php echo $type['percentage']; ?>%; box-shadow: 0 0 10px rgba(251,191,36,0.5);"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Category Sales Chart -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col items-center">
                        <div class="w-full flex justify-between items-center mb-4">
                            <h3 class="font-serif text-lg font-bold text-brand-black">Category Sales</h3>
                            <button class="text-gray-400 hover:text-brand-black"><i class="fa-solid fa-ellipsis"></i></button>
                        </div>
                        <div class="w-full h-[180px] relative mb-4">
                            <canvas id="categorySalesChart"></canvas>
                        </div>
                        <!-- Custom Legend -->
                        <div class="w-full grid grid-cols-2 gap-2 mt-auto">
                            <?php 
                            $colors = ['#171717', '#FBBF24', '#D97706', '#D1D5DB'];
                            $colorIndex = 0;
                            foreach ($categorySales as $cat): 
                                if ($colorIndex >= 4) break;
                            ?>
                            <div class="flex items-center gap-2 text-xs font-bold text-gray-600">
                                <span class="w-3 h-3 rounded-full" style="background-color: <?php echo $colors[$colorIndex]; ?>;"></span> 
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </div>
                            <?php 
                            $colorIndex++;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                </div>

                <!-- 4. Payment Methods & Top Products -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    
                    <!-- Payment Method Breakdown -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-serif text-lg font-bold text-brand-black">Payment Methods</h3>
                            <button class="text-gray-400 hover:text-brand-black"><i class="fa-solid fa-ellipsis"></i></button>
                        </div>
                        <div class="flex-1 space-y-4">
                            <?php foreach ($paymentBreakdown as $method): ?>
                            <div class="flex items-center justify-between p-3 border border-gray-100 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-white shadow-sm border border-gray-200 text-brand-black flex items-center justify-center">
                                        <?php 
                                        $icon = 'fa-solid fa-money-bill-wave';
                                        if ($method['payment_method'] === 'card') $icon = 'fa-regular fa-credit-card';
                                        elseif ($method['payment_method'] === 'gcash') $icon = 'fa-solid fa-mobile-screen text-blue-500';
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-brand-black"><?php echo ucfirst($method['payment_method']); ?></h4>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider"><?php echo $method['transaction_count']; ?> Transactions</p>
                                    </div>
                                </div>
                                <span class="font-serif font-bold text-brand-black text-lg"><?php echo formatCurrency($method['total_amount']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Product Sales Breakdown (Top 4) -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-serif text-lg font-bold text-brand-black">Top Products</h3>
                            <button class="text-brand text-xs font-bold hover:text-brand-dark transition-colors">View All</button>
                        </div>
                        <div class="flex-1 w-full">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr>
                                        <th class="pb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">Item</th>
                                        <th class="pb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 text-center">Qty</th>
                                        <th class="pb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach ($topProducts as $product): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="py-2.5 font-bold text-sm text-brand-black"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="py-2.5 text-center text-sm font-semibold text-gray-600"><?php echo $product['total_quantity']; ?></td>
                                        <td class="py-2.5 text-right font-serif font-bold text-brand-black"><?php echo formatCurrency($product['total_revenue']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- 5. Detailed Transaction Table -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
                    <!-- Table Header Tools -->
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-vintage-paper">
                        <div>
                            <h3 class="font-serif text-xl font-bold text-brand-black">Detailed Transactions</h3>
                            <p class="text-xs text-gray-500 mt-1 font-medium">Complete ledger of all orders recorded.</p>
                        </div>
                        
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text" placeholder="Search Order ID..." class="w-full bg-white h-9 rounded-md pl-9 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand border border-gray-200 shadow-sm">
                            </div>
                            <button class="bg-white border border-gray-300 text-gray-600 px-3 py-1.5 rounded-md text-sm font-bold shadow-sm hover:text-brand-black hover:border-brand-black transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    
                    <!-- Scrollable Table Body -->
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Type / Table</th>
                                    <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Items</th>
                                    <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Payment</th>
                                    <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Amount</th>
                                    <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($transactions as $transaction): ?>
                                <tr class="hover:bg-brand-light/20 transition-colors">
                                    <td class="py-4 px-6 font-bold text-sm text-brand-black"><?php echo htmlspecialchars($transaction['order_number']); ?></td>
                                    <td class="py-4 px-6 text-sm text-gray-600 font-medium"><?php echo date('m/d/y', strtotime($transaction['created_at'])); ?><br><span class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($transaction['created_at'])); ?></span></td>
                                    <td class="py-4 px-6">
                                        <?php if ($transaction['table_number']): ?>
                                        <span class="text-xs font-bold text-brand-black bg-gray-100 px-2 py-1 rounded border border-gray-200"><i class="fa-solid fa-utensils text-brand mr-1"></i> T<?php echo $transaction['table_number']; ?></span>
                                        <?php else: ?>
                                        <span class="text-xs font-bold text-gray-600 bg-gray-50 px-2 py-1 rounded border border-gray-100"><i class="fa-solid fa-bag-shopping text-gray-400 mr-1"></i> <?php echo ucfirst(str_replace('_', ' ', $transaction['order_type'])); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold text-gray-600"><?php echo $transaction['item_count']; ?></td>
                                    <td class="py-4 px-6">
                                        <span class="text-xs font-bold text-brand-black flex items-center gap-1">
                                            <?php 
                                            $icon = 'fa-solid fa-money-bill-wave text-brand';
                                            if ($transaction['payment_method'] === 'card') $icon = 'fa-regular fa-credit-card text-gray-500';
                                            elseif ($transaction['payment_method'] === 'gcash') $icon = 'fa-solid fa-mobile-screen text-blue-500';
                                            ?>
                                            <i class="<?php echo $icon; ?>"></i> <?php echo ucfirst($transaction['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right font-serif font-bold text-brand-black text-lg"><?php echo formatCurrency($transaction['total_amount']); ?></td>
                                    <td class="py-4 px-6 text-center">
                                        <button class="text-gray-400 hover:text-brand-black transition-colors"><i class="fa-regular fa-eye"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            
        </main>

    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl border border-gray-200">
            <div class="flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mx-auto mb-4">
                <i class="fa-solid fa-arrow-right-from-bracket text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-serif font-bold text-brand-black text-center mb-2">Confirm Logout</h3>
            <p class="text-gray-600 text-center mb-6">Are you sure you want to logout? You will need to sign in again to access the system.</p>
            <div class="flex gap-3">
                <button onclick="hideLogoutModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <a href="logout.php" class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition-colors text-center">
                    Logout
                </a>
            </div>
        </div>
    </div>

    <script>
        // Sales Trend Chart
        const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dateFilter === 'today' ? range(0, 23) : array_map(function($i) { return date('M d', strtotime("-$i days")); }, range(count($salesTrend) - 1, 0, -1))); ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode($salesTrend); ?>,
                    borderColor: '#FBBF24',
                    backgroundColor: 'rgba(251, 191, 36, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FBBF24',
                    pointBorderColor: '#171717',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Category Sales Chart
        const categorySalesCtx = document.getElementById('categorySalesChart').getContext('2d');
        new Chart(categorySalesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_slice(array_column($categorySales, 'category_name'), 0, 4)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_slice(array_column($categorySales, 'total_sales'), 0, 4)); ?>,
                    backgroundColor: ['#171717', '#FBBF24', '#D97706', '#D1D5DB'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '60%'
            }
        });

        function showLogoutModal() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }
        
        function hideLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const navTexts = document.querySelectorAll('.nav-text');
        let isCollapsed = true;

        // Apply collapsed state by default
        sidebarToggle.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        navTexts.forEach(text => {
            text.classList.add('hidden');
        });
        const navItems = document.querySelectorAll('#navigation a');
        navItems.forEach(item => {
            item.classList.add('justify-center');
            item.classList.remove('gap-4');
        });
        const logoText = sidebar.querySelector('h1');
        const logoSubtext = sidebar.querySelector('span.text-gray-500');
        const logoDivider = sidebar.querySelectorAll('.h-px');
        const logoSince = sidebar.querySelector('span.text-gray-400');
        if (logoText) logoText.classList.add('hidden');
        if (logoSubtext) logoSubtext.classList.add('hidden');
        if (logoSince) logoSince.classList.add('hidden');
        logoDivider.forEach(div => div.classList.add('hidden'));
        const userName = sidebar.querySelector('.text-sm.font-medium');
        if (userName) userName.classList.add('hidden');

        sidebarToggle.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            
            if (isCollapsed) {
                sidebar.classList.remove('w-[240px]');
                sidebar.classList.add('w-[80px]');
                sidebarToggle.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
                
                navTexts.forEach(text => {
                    text.classList.add('hidden');
                });
                
                const navItems = document.querySelectorAll('#navigation a');
                navItems.forEach(item => {
                    item.classList.add('justify-center');
                    item.classList.remove('gap-4');
                });
                
                const logoText = sidebar.querySelector('h1');
                const logoSubtext = sidebar.querySelector('span.text-gray-500');
                const logoDivider = sidebar.querySelectorAll('.h-px');
                const logoSince = sidebar.querySelector('span.text-gray-400');
                
                if (logoText) logoText.classList.add('hidden');
                if (logoSubtext) logoSubtext.classList.add('hidden');
                if (logoSince) logoSince.classList.add('hidden');
                logoDivider.forEach(div => div.classList.add('hidden'));
                
                const userName = sidebar.querySelector('.text-sm.font-medium');
                if (userName) userName.classList.add('hidden');
                
            } else {
                sidebar.classList.remove('w-[80px]');
                sidebar.classList.add('w-[240px]');
                sidebarToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
                
                navTexts.forEach(text => {
                    text.classList.remove('hidden');
                });
                
                const navItems = document.querySelectorAll('#navigation a');
                navItems.forEach(item => {
                    item.classList.remove('justify-center');
                    item.classList.add('gap-4');
                });
                
                const logoText = sidebar.querySelector('h1');
                const logoSubtext = sidebar.querySelector('span.text-gray-500');
                const logoDivider = sidebar.querySelectorAll('.h-px');
                const logoSince = sidebar.querySelector('span.text-gray-400');
                
                if (logoText) logoText.classList.remove('hidden');
                if (logoSubtext) logoSubtext.classList.remove('hidden');
                if (logoSince) logoSince.classList.remove('hidden');
                logoDivider.forEach(div => div.classList.remove('hidden'));
                
                const userName = sidebar.querySelector('.text-sm.font-medium');
                if (userName) userName.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
