<?php
require_once __DIR__ . '/../php/auth.php';
$admin = requireAdmin();
require_once __DIR__ . '/../php/db.php';

$msg = '';

// ── THÊM SẢN PHẨM ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $cat_id   = (int)($_POST['category_id'] ?? 0);
        $price    = (int)($_POST['price'] ?? 0);
        $stock    = (int)($_POST['stock'] ?? 0);
        $brand    = trim($_POST['brand'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $img      = trim($_POST['image_url'] ?? '');
        $slug     = strtolower(preg_replace('/\s+/', '-', $name)) . '-' . time();

        if ($name && $cat_id && $price) {
            $pdo->prepare("
                INSERT INTO products (category_id, name, slug, description, price, stock, image_url, brand)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$cat_id, $name, $slug, $desc, $price, $stock, $img, $brand]);
            $msg = ['type' => 'success', 'text' => 'Thêm sản phẩm thành công!'];
        } else {
            $msg = ['type' => 'error', 'text' => 'Vui lòng điền đầy đủ thông tin bắt buộc.'];
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$id]);
        $msg = ['type' => 'success', 'text' => 'Đã ẩn sản phẩm.'];
    }

    if ($action === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $price = (int)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $brand = trim($_POST['brand'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $img   = trim($_POST['image_url'] ?? '');
        $cat   = (int)($_POST['category_id'] ?? 0);

        $pdo->prepare("
            UPDATE products SET name=?, price=?, stock=?, brand=?, description=?, image_url=?, category_id=?
            WHERE id=?
        ")->execute([$name, $price, $stock, $brand, $desc, $img, $cat, $id]);
        $msg = ['type' => 'success', 'text' => 'Cập nhật sản phẩm thành công!'];
    }
}

// Lấy danh mục
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Lấy sản phẩm (có filter)
$search = trim($_GET['search'] ?? '');
$cat_filter = (int)($_GET['cat'] ?? 0);
$sql  = "SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1";
$params = [];
if ($search) { $sql .= " AND p.name LIKE ?"; $params[] = "%$search%"; }
if ($cat_filter) { $sql .= " AND p.category_id = ?"; $params[] = $cat_filter; }
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - Admin</title>
    <link rel="icon" href="/may_tinh_sucvn/images/logo-1.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/font-awesome.css">
    <link rel="stylesheet" href="/may_tinh_sucvn/css/admin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h1><i class="fa fa-cubes"></i> Quản lý Sản phẩm</h1>
            <button class="btn-primary" onclick="toggleForm()"><i class="fa fa-plus"></i> Thêm sản phẩm</button>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
        <?php endif; ?>

        <!-- Form thêm sản phẩm -->
        <div class="card" id="addForm" style="display:none">
            <div class="card-header"><h3><i class="fa fa-plus"></i> Thêm sản phẩm mới</h3></div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input type="text" name="name" required placeholder="VD: Intel Core i5-12400F">
                    </div>
                    <div class="form-group">
                        <label>Danh mục *</label>
                        <select name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá (VNĐ) *</label>
                        <input type="number" name="price" required placeholder="VD: 3500000">
                    </div>
                    <div class="form-group">
                        <label>Số lượng tồn kho</label>
                        <input type="number" name="stock" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Thương hiệu</label>
                        <input type="text" name="brand" placeholder="VD: Intel, AMD, ASUS...">
                    </div>
                    <div class="form-group">
                        <label>URL hình ảnh</label>
                        <input type="text" name="image_url" placeholder="../images/product/cpu1.jpg">
                    </div>
                    <div class="form-group full-width">
                        <label>Mô tả sản phẩm</label>
                        <textarea name="description" rows="3" placeholder="Mô tả chi tiết..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Lưu sản phẩm</button>
                <button type="button" class="btn-secondary" onclick="toggleForm()">Huỷ</button>
            </form>
        </div>

        <!-- Tìm kiếm & lọc -->
        <div class="card">
            <form method="GET" class="filter-bar">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Tìm tên sản phẩm...">
                <select name="cat">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $cat_filter == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary">Lọc</button>
                <a href="products.php" class="btn-secondary">Xoá lọc</a>
            </form>

            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Hình</th><th>Tên sản phẩm</th><th>Danh mục</th><th>Giá</th><th>Tồn kho</th><th>Thao tác</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="empty">Không có sản phẩm nào</td></tr>
                    <?php else: foreach ($products as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>
                                <?php if ($p['image_url']): ?>
                                    <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px">
                                <?php else: ?>
                                    <span class="no-img"><i class="fa fa-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><b><?= htmlspecialchars($p['name']) ?></b><br><small><?= htmlspecialchars($p['brand']) ?></small></td>
                            <td><?= htmlspecialchars($p['cat_name']) ?></td>
                            <td><?= number_format($p['price']) ?>₫</td>
                            <td><span class="badge badge-<?= $p['stock'] <= 5 ? 'pending' : 'done' ?>"><?= $p['stock'] ?></span></td>
                            <td>
                                <button class="btn-sm btn-edit" onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="fa fa-edit"></i></button>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Ẩn sản phẩm này?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Sửa -->
    <div class="modal" id="editModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Sửa sản phẩm</h3>
                <button onclick="closeEdit()">✕</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tên sản phẩm</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label>Danh mục</label>
                        <select name="category_id" id="edit_cat">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá (VNĐ)</label>
                        <input type="number" name="price" id="edit_price" required>
                    </div>
                    <div class="form-group">
                        <label>Tồn kho</label>
                        <input type="number" name="stock" id="edit_stock">
                    </div>
                    <div class="form-group">
                        <label>Thương hiệu</label>
                        <input type="text" name="brand" id="edit_brand">
                    </div>
                    <div class="form-group">
                        <label>URL hình ảnh</label>
                        <input type="text" name="image_url" id="edit_img">
                    </div>
                    <div class="form-group full-width">
                        <label>Mô tả</label>
                        <textarea name="description" id="edit_desc" rows="3"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Cập nhật</button>
                <button type="button" class="btn-secondary" onclick="closeEdit()">Huỷ</button>
            </form>
        </div>
    </div>

    <script>
    function toggleForm() {
        const f = document.getElementById('addForm');
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }
    function openEdit(p) {
        document.getElementById('edit_id').value    = p.id;
        document.getElementById('edit_name').value  = p.name;
        document.getElementById('edit_price').value = p.price;
        document.getElementById('edit_stock').value = p.stock;
        document.getElementById('edit_brand').value = p.brand || '';
        document.getElementById('edit_img').value   = p.image_url || '';
        document.getElementById('edit_desc').value  = p.description || '';
        document.getElementById('edit_cat').value   = p.category_id;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEdit() {
        document.getElementById('editModal').style.display = 'none';
    }
    </script>
</body>
</html>
