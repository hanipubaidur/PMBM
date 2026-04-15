<?php
$current_page = basename($_SERVER['PHP_SELF']);
$menu_items = [
    'index.php' => ['icon' => 'bi-speedometer2', 'text' => 'Dashboard'],
    'peserta.php' => ['icon' => 'bi-people', 'text' => 'Data Peserta'],
    'verifikasi.php' => ['icon' => 'bi-check-circle', 'text' => 'Verifikasi'],
    'pengumuman.php' => ['icon' => 'bi-megaphone', 'text' => 'Pengumuman'],
    'pengaturan.php' => ['icon' => 'bi-gear', 'text' => 'Pengaturan'],
    'export_data.php' => ['icon' => 'bi-arrow-left-right', 'text' => 'Export Data']
];
?>
<div class="sidebar bg-white p-3 admin-sidebar border-end shadow-sm">
    <div class="mb-3 px-3 text-muted small fw-bold text-uppercase" style="letter-spacing: 1px; font-size: 0.7rem;">Menu Utama</div>
    <div class="d-flex flex-column gap-1">
        <?php foreach($menu_items as $url => $item): 
            $isActive = $current_page === $url;
        ?>
            <a href="<?= $url ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <i class="bi <?= $item['icon'] ?> fs-5 me-3"></i>
                <span class="fw-medium"><?= $item['text'] ?></span>
            </a>
        <?php endforeach; ?>
        
        <hr class="my-2 text-muted">
        
        <a href="logout.php" class="sidebar-link text-danger logout-link">
            <i class="bi bi-box-arrow-left fs-5 me-3"></i>
            <span class="fw-medium">Logout</span>
        </a>
    </div>
</div>

<style>
    .admin-sidebar {
        position: relative;
        z-index: 1;
        min-height: calc(100vh - 76px);
    }
    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 10px 18px;
        color: #495057;
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.25s ease;
    }
    .sidebar-link:hover:not(.active) {
        background-color: #f8f9fa;
        color: #0d6efd;
        transform: translateX(4px);
    }
    .sidebar-link.logout-link:hover {
        background-color: #fff5f5;
        color: #dc3545;
    }
    .sidebar-link.active {
        background-color: #0d6efd;
        color: white;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
    }
    
    @media (max-width: 767px) {
        .admin-sidebar { 
            min-height: auto; 
            border-right: none !important; 
            border-bottom: 1px solid #dee2e6; 
            box-shadow: none !important;
        }
    }
    @media (min-width: 768px) {
        .admin-sidebar { position: sticky; top: 76px; }
    }
</style>