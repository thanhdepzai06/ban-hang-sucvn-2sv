<?php
require_once __DIR__ . '/../php/auth.php';
$admin = requireAdmin();
require_once __DIR__ . '/../php/db.php';

$msg = '';

// Khoá / mở khoá tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $active = (int)($_POST['is_active'] ?? 0);
    if ($id && $id !== $admin['id']) { // không tự khoá mình
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'customer'")->execute([$active, $id]);
        $msg = ['type' => 'success', 'text' => $active ? 'Đã mở khoá tài khoản.' : 'Đã khoá tài khoản.'];
    }
}

$search = trim($_GET['search'] ?? '');
$sql    = "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS total_orders
           FROM users u WHERE u.role = 'customer'";
$params = [];
if ($search) { $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)"; $params = ["%$search%","%$search%","%$search%"]; }
$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khách hàng - Admin</title>
    <link rel="icon" href="/may_tinh_sucvn/images/logo-1.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/font-awesome.css">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h1><i class="fa fa-users"></i> Quản lý Khách hàng</h1>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="GET" class="filter-bar">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Tìm tên, email, username...">
                <button type="submit" class="btn-primary">Tìm</button>
                <a href="users.php" class="btn-secondary">Xoá lọc</a>
            </form>

            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Họ tên</th><th>Username</th><th>Email</th><th>SĐT</th><th>Đơn hàng</th><th>Ngày tạo</th><th>Trạng thái</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" class="empty">Không có khách hàng nào</td></tr>
                    <?php else: foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><b><?= htmlspecialchars($u['full_name']) ?></b></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                            <td><?= $u['total_orders'] ?></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <?php if ($u['is_active']): ?>
                                        <input type="hidden" name="is_active" value="0">
                                        <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Khoá tài khoản này?')">
                                            <i class="fa fa-lock"></i> Khoá
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="is_active" value="1">
                                        <button type="submit" class="btn-sm btn-edit">
                                            <i class="fa fa-unlock"></i> Mở khoá
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
