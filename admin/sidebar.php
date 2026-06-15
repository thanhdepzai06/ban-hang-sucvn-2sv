<?php
$current = basename($_SERVER['PHP_SELF']);
function navItem($file, $icon, $label, $current) {
    $active = ($current === $file) ? 'active' : '';
    return "<li class='$active'><a href='$file'><i class='fa fa-$icon'></i> $label</a></li>";
}
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="/may_tinh_sucvn/images/logo-1.png" alt="TKL Computer">
        <span>Admin Panel</span>
    </div>
    <nav>
        <ul>
            <?= navItem('index.php',    'tachometer',      'Dashboard',      $current) ?>
            <?= navItem('products.php', 'cubes',           'Sản phẩm',       $current) ?>
            <?= navItem('orders.php',   'shopping-cart',   'Đơn hàng',       $current) ?>
            <?= navItem('revenue.php',  'bar-chart',       'Doanh thu',      $current) ?>
            <?= navItem('users.php',    'users',           'Khách hàng',     $current) ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="../index.html"><i class="fa fa-home"></i> Về trang chủ</a>
        <a href="#" onclick="doLogout()"><i class="fa fa-sign-out"></i> Đăng xuất</a>
    </div>
</div>
<script>
async function doLogout() {
    const fd = new FormData();
    fd.append('action', 'logout');
    await fetch('/may_tinh_sucvn/php/api_auth.php', { method: 'POST', body: fd });
    window.location.href = '/may_tinh_sucvn/php/login.php';
}
</script>
