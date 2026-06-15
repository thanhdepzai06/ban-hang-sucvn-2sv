<?php
require_once __DIR__ . '/../php/auth.php';
$admin = requireAdmin();
require_once __DIR__ . '/../php/db.php';

// Doanh thu theo tháng
$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
           COUNT(*) AS total_orders,
           SUM(total_price) AS revenue
    FROM orders WHERE status = 'done'
    GROUP BY month ORDER BY month DESC LIMIT 12
")->fetchAll();

// Sản phẩm bán chạy
$top_products = $pdo->query("
    SELECT p.name, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status = 'done'
    GROUP BY p.id ORDER BY sold DESC LIMIT 10
")->fetchAll();

// Tổng hợp
$summary = $pdo->query("
    SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_price),0) AS total_revenue
    FROM orders WHERE status = 'done'
")->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doanh thu - Admin</title>
    <link rel="icon" href="/may_tinh_sucvn/images/logo-1.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/font-awesome.css">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h1><i class="fa fa-bar-chart"></i> Thống kê Doanh thu</h1>
        </div>

        <!-- Tổng hợp -->
        <div class="stats-grid">
            <div class="stat-card green">
                <i class="fa fa-check-circle"></i>
                <div>
                    <p>Đơn hoàn thành</p>
                    <h2><?= number_format($summary['total_orders']) ?></h2>
                </div>
            </div>
            <div class="stat-card red">
                <i class="fa fa-money"></i>
                <div>
                    <p>Tổng doanh thu</p>
                    <h2><?= number_format($summary['total_revenue']) ?>₫</h2>
                </div>
            </div>
        </div>

        <div class="two-col">
            <!-- Biểu đồ doanh thu -->
            <div class="card">
                <div class="card-header"><h3><i class="fa fa-line-chart"></i> Doanh thu theo tháng</h3></div>
                <canvas id="revenueChart" height="250"></canvas>
            </div>

            <!-- Top sản phẩm bán chạy -->
            <div class="card">
                <div class="card-header"><h3><i class="fa fa-trophy"></i> Sản phẩm bán chạy</h3></div>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Sản phẩm</th><th>Đã bán</th><th>Doanh thu</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                            <tr><td colspan="4" class="empty">Chưa có dữ liệu</td></tr>
                        <?php else: foreach ($top_products as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= $p['sold'] ?></td>
                                <td><?= number_format($p['revenue']) ?>₫</td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bảng doanh thu theo tháng -->
        <div class="card">
            <div class="card-header"><h3><i class="fa fa-table"></i> Chi tiết theo tháng</h3></div>
            <table class="data-table">
                <thead>
                    <tr><th>Tháng</th><th>Số đơn</th><th>Doanh thu</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($monthly)): ?>
                        <tr><td colspan="3" class="empty">Chưa có dữ liệu</td></tr>
                    <?php else: foreach ($monthly as $m): ?>
                        <tr>
                            <td><?= $m['month'] ?></td>
                            <td><?= $m['total_orders'] ?></td>
                            <td><?= number_format($m['revenue']) ?>₫</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    const labels  = <?= json_encode(array_column(array_reverse($monthly), 'month')) ?>;
    const revenue = <?= json_encode(array_map('floatval', array_column(array_reverse($monthly), 'revenue'))) ?>;

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Doanh thu (₫)',
                data: revenue,
                backgroundColor: '#FAB005',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('vi-VN') + '₫' } } }
        }
    });
    </script>
</body>
</html>
