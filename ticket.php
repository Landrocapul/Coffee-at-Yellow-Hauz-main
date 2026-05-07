<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

// Check if user has cashier access (both cashier and admin can access tickets)
if (!hasCashierAccess()) {
    redirect('menu.php');
}

// Get current user info
$currentUser = getCurrentUser();
$allowedTicketFilters = ['active', 'completed', 'all'];
$ticketFilter = $_GET['filter'] ?? ($_SESSION['ticket_filter'] ?? 'active');
if (!in_array($ticketFilter, $allowedTicketFilters, true)) {
    $ticketFilter = 'active';
}
$_SESSION['ticket_filter'] = $ticketFilter;
$activeTabClass = 'px-3 py-1.5 rounded-md bg-brand-light text-brand-dark font-bold border border-brand/20 shadow-sm transition-all';
$inactiveTabClass = 'px-3 py-1.5 rounded-md text-gray-500 hover:text-brand-black font-semibold transition-all';

// Get active tickets (orders)
$stmt = $pdo->prepare("SELECT o.*, t.table_number, u.full_name as cashier_name 
                      FROM orders o 
                      LEFT JOIN tables t ON o.table_id = t.id 
                      LEFT JOIN users u ON o.cashier_id = u.id 
                      WHERE o.status IN ('pending', 'processing') 
                      ORDER BY o.created_at DESC");
$stmt->execute();
$activeTickets = $stmt->fetchAll();

// Get completed tickets today
$stmt = $pdo->prepare("SELECT o.*, t.table_number 
                      FROM orders o 
                      LEFT JOIN tables t ON o.table_id = t.id 
                      WHERE o.status = 'completed' 
                      AND DATE(o.created_at) = CURDATE()
                      ORDER BY o.created_at DESC
                      LIMIT 20");
$stmt->execute();
$completedTickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yellow Hauz POS - Tickets</title>
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
        
        <!-- LEFT SIDEBAR -->
        <aside id="sidebar" class="w-[80px] bg-white border-r border-vintage-border flex flex-col justify-between py-6 px-4 shrink-0 z-10 transition-all duration-300 ease-in-out">
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
                <nav id="navigation" class="space-y-2">
                    <a href="menu.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-mug-hot w-5 text-center"></i> <span class="nav-text">Menu</span>
                    </a>
                    <a href="table.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-utensils w-5 text-center"></i> <span class="nav-text">Table Services</span>
                    </a>
                    <a href="ticket.php" class="flex items-center gap-4 bg-brand-black text-brand px-4 py-3.5 rounded-2xl font-semibold shadow-md transition-all">
                        <i class="fa-solid fa-receipt w-5 text-center"></i> <span class="nav-text">Tickets</span>
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="items.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-clipboard-list w-5 text-center"></i> <span class="nav-text">Manage Food Items</span>
                    </a>
                    <a href="sales.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-chart-line w-5 text-center"></i> <span class="nav-text">Sales Report</span>
                    </a>
                    <a href="analysis.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-chart-pie w-5 text-center"></i> <span class="nav-text">Product Analytics</span>
                    </a>
                    <a href="settings.php" class="flex items-center gap-4 text-gray-500 hover:text-brand-black hover:bg-gray-100 px-4 py-3.5 rounded-2xl font-medium transition-all">
                        <i class="fa-solid fa-gear w-5 text-center"></i> <span class="nav-text">Settings</span>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Bottom Users / Logout -->
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

        <main class="flex-1 flex flex-col relative bg-vintage-paper">
            <!-- Top Header -->
            <header class="h-[88px] flex items-center justify-between px-8 shrink-0 border-b border-gray-200/50">
                <button id="sidebarToggle" class="w-10 h-10 bg-white rounded-xl shadow-sm border border-gray-200 flex items-center justify-center text-gray-500 hover:text-brand-black">
                    <i class="fa-solid fa-bars"></i>
                </button>
                
                <h2 class="text-2xl font-serif font-bold text-brand-black tracking-wide">TICKETS</h2>
                
                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-white border border-gray-200 rounded-lg p-1 shadow-sm text-sm">
                        <a href="ticket.php?filter=active" class="<?php echo $ticketFilter === 'active' ? $activeTabClass : $inactiveTabClass; ?>">Active</a>
                        <a href="ticket.php?filter=completed" class="<?php echo $ticketFilter === 'completed' ? $activeTabClass : $inactiveTabClass; ?>">Completed</a>
                        <a href="ticket.php?filter=all" class="<?php echo $ticketFilter === 'all' ? $activeTabClass : $inactiveTabClass; ?>">All</a>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto px-8 pb-8 pt-6">
                <!-- Active Tickets -->
                <?php if ($ticketFilter === 'active' || $ticketFilter === 'all'): ?>
                <h3 class="text-lg font-serif font-bold text-brand-black mb-4">Active Tickets</h3>
                <div class="columns-1 md:columns-2 lg:columns-3 gap-6 mb-8">
                    <?php foreach ($activeTickets as $ticket): ?>
                    <div class="break-inside-avoid mb-6 bg-white rounded-2xl border-2 <?php echo $ticket['status'] === 'processing' ? 'border-brand' : 'border-gray-200'; ?> p-5 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full <?php echo $ticket['status'] === 'processing' ? 'bg-brand text-brand-black' : 'bg-gray-100 text-gray-600'; ?> flex items-center justify-center font-bold text-lg">
                                    <?php echo $ticket['table_number'] ? 'T' . $ticket['table_number'] : 'TA'; ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-brand-black"><?php echo htmlspecialchars($ticket['customer_name'] ?? 'Guest'); ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($ticket['created_at'])); ?></p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <?php 
                                        $orderType = $ticket['order_type'] ?? 'dine_in';
                                        echo 'DEBUG: ' . $orderType . ' - ';
                                        echo $orderType === 'dine_in' ? 'Dine In' : 'Take Out';
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded-md <?php echo $ticket['status'] === 'processing' ? 'bg-brand-light text-brand-dark' : 'bg-gray-100 text-gray-600'; ?> uppercase">
                                <?php echo $ticket['status']; ?>
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <?php 
                            $stmt = $pdo->prepare("SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?");
                            $stmt->execute([$ticket['id']]);
                            $items = $stmt->fetchAll();
                            foreach ($items as $item): 
                            ?>
                            <div class="flex justify-between items-center text-sm">
                                <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="text-gray-600"><?php echo $item['quantity']; ?>x</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <span class="font-bold text-brand-black"><?php echo formatCurrency($ticket['total_amount']); ?></span>
                            <div class="flex gap-2">
                                <button onclick="viewOrder(<?php echo (int)$ticket['id']; ?>)" class="px-3 py-1.5 bg-brand-light text-brand-dark rounded-lg text-xs font-bold hover:bg-brand hover:text-brand-black transition-colors">View</button>
                                <button onclick="completeOrder(<?php echo (int)$ticket['id']; ?>)" class="px-3 py-1.5 bg-brand-black text-brand rounded-lg text-xs font-bold hover:bg-gray-800 transition-colors">Complete</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($activeTickets)): ?>
                    <div class="col-span-full text-center py-12 text-gray-400">
                        <i class="fa-solid fa-ticket text-4xl mb-3"></i>
                        <p class="text-sm font-medium">No active tickets</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Completed Tickets -->
                <?php if ($ticketFilter === 'completed' || $ticketFilter === 'all'): ?>
                <h3 class="text-lg font-serif font-bold text-brand-black mb-4">Completed Today</h3>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Table</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($completedTickets as $ticket): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-6 font-bold text-sm text-brand-black"><?php echo htmlspecialchars($ticket['order_number']); ?></td>
                                <td class="py-4 px-6 text-sm text-gray-600"><?php echo htmlspecialchars($ticket['customer_name'] ?? 'Guest'); ?></td>
                                <td class="py-4 px-6 text-sm text-gray-600"><?php echo $ticket['table_number'] ? 'Table ' . $ticket['table_number'] : 'Take Away'; ?></td>
                                <td class="py-4 px-6 text-sm text-gray-600"><?php echo date('h:i A', strtotime($ticket['created_at'])); ?></td>
                                <td class="py-4 px-6 font-serif font-bold text-brand-black"><?php echo formatCurrency($ticket['total_amount']); ?></td>
                                <td class="py-4 px-6 text-center">
                                    <button onclick="viewOrder(<?php echo (int)$ticket['id']; ?>)" class="text-gray-400 hover:text-brand-black transition-colors"><i class="fa-regular fa-eye"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($completedTickets)): ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 text-sm">No completed tickets today</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
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

    <!-- Ticket Details Modal -->
    <div id="ticketDetailsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full mx-4 shadow-2xl border border-gray-200 max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-100 flex items-start justify-between gap-4">
                <div>
                    <p id="ticketDetailsOrderNumber" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Order</p>
                    <h3 id="ticketDetailsCustomer" class="text-2xl font-serif font-bold text-brand-black mt-1">Ticket Details</h3>
                    <p id="ticketDetailsMeta" class="text-sm text-gray-500 mt-1"></p>
                </div>
                <button type="button" onclick="hideTicketDetailsModal()" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500 hover:text-brand-black hover:bg-gray-200 transition-colors shrink-0">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto">
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3">
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Status</p>
                        <p id="ticketDetailsStatus" class="text-sm font-bold text-brand-black mt-1">-</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3">
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Payment</p>
                        <p id="ticketDetailsPayment" class="text-sm font-bold text-brand-black mt-1">-</p>
                    </div>
                </div>

                <div id="ticketDetailsItems" class="space-y-3 mb-5"></div>

                <div class="border-t border-gray-200 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span id="ticketDetailsSubtotal" class="font-bold text-brand-black">-</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tax</span>
                        <span id="ticketDetailsTax" class="font-bold text-brand-black">-</span>
                    </div>
                    <div class="flex justify-between text-lg pt-2 border-t border-gray-100">
                        <span class="font-bold text-brand-black">Total</span>
                        <span id="ticketDetailsTotal" class="font-bold text-brand-black">-</span>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 bg-gray-50 flex gap-3">
                <button type="button" onclick="hideTicketDetailsModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition-colors">
                    Close
                </button>
                <button id="ticketDetailsCompleteButton" type="button" class="flex-1 bg-brand-black text-brand py-3 rounded-xl font-bold hover:bg-gray-800 transition-colors hidden">
                    Complete
                </button>
            </div>
        </div>
    </div>

    <script>
        function showLogoutModal() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }
        
        function hideLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function formatPeso(amount) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(amount) || 0);
        }

        function formatOrderType(type) {
            return type === 'dine_in' ? 'Dine In' : type === 'take_away' ? 'Take Out' : 'Delivery';
        }

        function formatPaymentMethod(method) {
            if (method === 'gcash') return 'GCash';
            return method ? method.charAt(0).toUpperCase() + method.slice(1) : '-';
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        }

        function hideTicketDetailsModal() {
            document.getElementById('ticketDetailsModal').classList.add('hidden');
        }

        function viewOrder(orderId) {
            const modal = document.getElementById('ticketDetailsModal');
            const itemsContainer = document.getElementById('ticketDetailsItems');
            const completeButton = document.getElementById('ticketDetailsCompleteButton');

            itemsContainer.innerHTML = '<p class="text-center text-sm text-gray-400 py-6">Loading ticket...</p>';
            completeButton.classList.add('hidden');
            completeButton.onclick = null;
            modal.classList.remove('hidden');

            fetch('api.php?action=get_order_details&order_id=' + encodeURIComponent(orderId))
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        itemsContainer.innerHTML = '<p class="text-center text-sm text-red-600 py-6">' + (data.error || 'Unable to load ticket.') + '</p>';
                        return;
                    }

                    const order = data.data.order;
                    const items = data.data.items || [];
                    const tableLabel = order.table_number ? 'Table ' + order.table_number : 'Take Out';
                    const createdAt = new Date(order.created_at.replace(' ', 'T'));

                    document.getElementById('ticketDetailsOrderNumber').textContent = 'Order #' + order.order_number;
                    document.getElementById('ticketDetailsCustomer').textContent = order.customer_name || 'Guest';
                    document.getElementById('ticketDetailsMeta').textContent = tableLabel + ' · ' + formatOrderType(order.order_type) + ' · ' + createdAt.toLocaleString([], {
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit'
                    });
                    document.getElementById('ticketDetailsStatus').textContent = order.status;
                    document.getElementById('ticketDetailsPayment').textContent = formatPaymentMethod(order.payment_method);
                    document.getElementById('ticketDetailsSubtotal').textContent = formatPeso(order.subtotal);
                    document.getElementById('ticketDetailsTax').textContent = formatPeso(order.tax_amount);
                    document.getElementById('ticketDetailsTotal').textContent = formatPeso(order.total_amount);

                    itemsContainer.innerHTML = items.map(item => `
                        <div class="flex justify-between gap-4 rounded-xl border border-gray-200 bg-white p-3">
                            <div>
                                <p class="font-bold text-sm text-brand-black">${escapeHtml(item.name)}</p>
                                <p class="text-xs text-gray-500 mt-1">${formatPeso(item.unit_price)} each</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-brand-black">${item.quantity}x</p>
                                <p class="text-xs text-gray-500 mt-1">${formatPeso(item.total_price)}</p>
                            </div>
                        </div>
                    `).join('') || '<p class="text-center text-sm text-gray-400 py-6">No items found</p>';

                    if (order.status === 'pending' || order.status === 'processing') {
                        completeButton.classList.remove('hidden');
                        completeButton.onclick = () => completeOrder(Number(order.id));
                    }
                })
                .catch(() => {
                    itemsContainer.innerHTML = '<p class="text-center text-sm text-red-600 py-6">Unable to load ticket.</p>';
                });
        }

        function completeOrder(orderId) {
            fetch('api.php?action=update_order_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'completed'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                    return;
                }

                alert(data.error || 'Unable to complete order.');
            })
            .catch(() => {
                alert('Unable to complete order.');
            });
        }

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const navTexts = document.querySelectorAll('.nav-text');
        let isCollapsed = localStorage.getItem('sidebarCollapsed') !== 'false';

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

        if (!isCollapsed) {
            sidebar.classList.remove('w-[80px]');
            sidebar.classList.add('w-[240px]');
            sidebarToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
            navTexts.forEach(text => text.classList.remove('hidden'));
            navItems.forEach(item => {
                item.classList.remove('justify-center');
                item.classList.add('gap-4');
            });
            if (logoText) logoText.classList.remove('hidden');
            if (logoSubtext) logoSubtext.classList.remove('hidden');
            if (logoSince) logoSince.classList.remove('hidden');
            logoDivider.forEach(div => div.classList.remove('hidden'));
            if (userName) userName.classList.remove('hidden');
        }

        sidebarToggle.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            localStorage.setItem('sidebarCollapsed', String(isCollapsed));
            
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
