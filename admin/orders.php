<?php
require_once __DIR__ . '/../php/auth.php';
$admin = requireAdmin();
require_once __DIR__ . '/../php/db.php';

$msg = '';

// Cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $id     = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';
    $allowed = ['pending','confirmed','shipping','done','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $id]);
        $msg = ['type' => 'success', 'text' => 'Cập nhật trạng thái thành công!'];
    }
}

// Lọc theo trạng thái
$filter = $_GET['status'] ?? '';
$sql    = "SELECT o.*, u.full_name, u.phone AS user_phone FROM orders o JOIN users u ON u.id = o.user_id";
$params = [];
if ($filter) { $sql .= " WHERE o.status = ?"; $params[] = $filter; }
$sql .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

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
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - Admin</title>
    <link rel="icon" href="/may_tinh_sucvn/images/logo-1.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/font-awesome.css">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h1><i class="fa fa-shopping-cart"></i> Quản lý Đơn hàng</h1>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
        <?php endif; ?>

        <!-- Lọc trạng thái -->
        <div class="card">
            <div class="filter-bar">
                <?php
                $statuses = ['' => 'Tất cả', 'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận',
                             'shipping' => 'Đang giao', 'done' => 'Hoàn thành', 'cancelled' => 'Đã huỷ'];
                foreach ($statuses as $val => $label):
                    $active = $filter === $val ? 'btn-primary' : 'btn-secondary';
                ?>
                    <a href="?status=<?= $val ?>" class="btn-sm <?= $active ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Khách hàng</th><th>SĐT</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày đặt</th><th>Thao tác</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="empty">Không có đơn hàng nào</td></tr>
                    <?php else: foreach ($orders as $o): ?>
                        <tr>
                            <td><b>#<?= $o['id'] ?></b></td>
                            <td><?= htmlspecialchars($o['full_name']) ?></td>
                            <td><?= htmlspecialchars($o['phone']) ?></td>
                            <td><b><?= number_format($o['total_price']) ?>₫</b></td>
                            <td><span class="badge badge-<?= $o['status'] ?>"><?= statusLabel($o['status']) ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td>
                                <button class="btn-sm btn-edit" onclick="openOrder(<?= htmlspecialchars(json_encode($o)) ?>)">
                                    <i class="fa fa-eye"></i> Chi tiết
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal chi tiết đơn hàng -->
    <div class="modal" id="orderModal">
        <div class="modal-box" style="max-width:550px">
            <div class="modal-header">
                <h3 id="modal_title">Chi tiết đơn hàng</h3>
                <button onclick="document.getElementById('orderModal').style.display='none'">✕</button>
            </div>
            <div id="modal_info" style="margin-bottom:16px;line-height:2"></div>
            <form method="POST">
                <input type="hidden" name="order_id" id="modal_order_id">
                <div class="form-group">
                    <label>Cập nhật trạng thái</label>
                    <select name="status" id="modal_status">
                        <?php foreach (['pending','confirmed','shipping','done','cancelled'] as $s): ?>
                            <option value="<?= $s ?>"><?= statusLabel($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Cập nhật</button>
            </form>
        </div>
    </div>

    <script>
    function openOrder(o) {
        document.getElementById('modal_title').textContent = 'Đơn hàng #' + o.id;
        document.getElementById('modal_order_id').value   = o.id;
        document.getElementById('modal_status').value     = o.status;
        document.getElementById('modal_info').innerHTML   =
            '<b>Khách:</b> ' + o.full_name + '<br>' +
            '<b>SĐT:</b> ' + o.phone + '<br>' +
            '<b>Địa chỉ:</b> ' + o.shipping_address + '<br>' +
            '<b>Ghi chú:</b> ' + (o.note || '—') + '<br>' +
            '<b>Tổng tiền:</b> ' + Number(o.total_price).toLocaleString('vi-VN') + '₫';
        document.getElementById('orderModal').style.display = 'flex';
    }
    </script>
</body>
</html>
