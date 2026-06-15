<?php
require_once __DIR__ . '/../php/auth.php';
$admin = requireAdmin();
require_once __DIR__ . '/../php/db.php';

// Thống kê tổng quan
$stats = [];

$stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$stats['total_users']    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$stats['total_orders']   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stats['total_revenue']  = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status = 'done'")->fetchColumn();
$stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Đơn hàng mới nhất
$recent_orders = $pdo->query("
    SELECT o.id, u.full_name, o.total_price, o.status, o.created_at
    FROM orders o JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();

// Sản phẩm sắp hết hàng
$low_stock = $pdo->query("
    SELECT p.name, p.stock, c.name AS category
    FROM products p JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1 AND p.stock <= 5
    ORDER BY p.stock ASC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - TKL Computer</title>
    <link rel="icon" href="/may_tinh_sucvn/images/logo-1.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/font-awesome.css">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h1><i class="fa fa-tachometer"></i> Dashboard</h1>
            <span>Xin chào, <b><?= htmlspecialchars($admin['full_name']) ?></b></span>
        </div>

        <!-- Thống kê -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <i class="fa fa-cubes"></i>
                <div>
                    <p>Sản phẩm</p>
                    <h2><?= number_format($stats['total_products']) ?></h2>
                </div>
            </div>
            <div class="stat-card green">
                <i class="fa fa-users"></i>
                <div>
                    <p>Khách hàng</p>
                    <h2><?= number_format($stats['total_users']) ?></h2>
                </div>
            </div>
            <div class="stat-card yellow">
                <i class="fa fa-shopping-cart"></i>
                <div>
                    <p>Đơn hàng</p>
                    <h2><?= number_format($stats['total_orders']) ?></h2>
                </div>
            </div>
            <div class="stat-card red">
                <i class="fa fa-money"></i>
                <div>
                    <p>Doanh thu</p>
                    <h2><?= number_format($stats['total_revenue']) ?>₫</h2>
                </div>
            </div>
        </div>

        <div class="two-col">
            <!-- Đơn hàng mới nhất -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa fa-list"></i> Đơn hàng mới nhất</h3>
                    <a href="orders.php">Xem tất cả →</a>
                </div>
                <?php if ($stats['pending_orders'] > 0): ?>
                    <div class="alert-warning">⚠️ Có <b><?= $stats['pending_orders'] ?></b> đơn hàng đang chờ xác nhận!</div>
                <?php endif; ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="4" class="empty">Chưa có đơn hàng nào</td></tr>
                        <?php else: foreach ($recent_orders as $o): ?>
                            <tr>
                                <td>#<?= $o['id'] ?></td>
                                <td><?= htmlspecialchars($o['full_name']) ?></td>
                                <td><?= number_format($o['total_price']) ?>₫</td>
                                <td><span class="badge badge-<?= $o['status'] ?>"><?= statusLabel($o['status']) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sản phẩm sắp hết -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa fa-exclamation-triangle"></i> Sắp hết hàng</h3>
                    <a href="products.php">Quản lý →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Sản phẩm</th><th>Danh mục</th><th>Còn lại</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($low_stock)): ?>
                            <tr><td colspan="3" class="empty">Không có sản phẩm sắp hết hàng</td></tr>
                        <?php else: foreach ($low_stock as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['category']) ?></td>
                                <td><span class="badge badge-<?= $p['stock'] == 0 ? 'cancelled' : 'pending' ?>"><?= $p['stock'] ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php
function statusLabel($s) {
    return match($s) {
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping'  => 'Đang giao',
        'done'      => 'Hoàn thành',
        'cancelled' => 'Đã huỷ',
        default     => $s
    };
}
?>
</body>
</html>
