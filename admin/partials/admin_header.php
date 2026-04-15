<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom admin-navbar py-2">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="https://cdn.postimage.me/2026/04/11/logo-kemenag.png" alt="Logo" height="45" class="me-3 drop-shadow-sm">
            <div class="d-flex flex-column">
                <span class="fw-bold text-primary" style="font-size: 1.1rem; letter-spacing: 0.5px;">ADMIN PMBM</span>
                <small class="text-secondary fw-medium" style="font-size: 0.75rem;">MAN 1 MUSI RAWAS</small>
            </div>
        </a>
        
        <button class="navbar-toggler border-0 shadow-none text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="bi bi-list fs-1"></i>
        </button>

        <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
            <ul class="navbar-nav me-auto"></ul>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block me-2">
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">Administrator</div>
                    <div class="text-success fw-medium" style="font-size: 0.75rem;">
                        <i class="bi bi-circle-fill" style="font-size: 0.4rem; vertical-align: middle;"></i> Online
                    </div>
                </div>
                <a href="../logout.php" class="btn btn-light text-danger fw-medium rounded-pill px-4 shadow-sm border border-danger-subtle custom-logout">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
    .admin-navbar { 
        position: fixed; 
        top: 0; right: 0; left: 0; 
        z-index: 1030; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
    }
    .custom-logout {
        transition: all 0.3s ease;
    }
    .custom-logout:hover { 
        background-color: #dc3545 !important; 
        color: white !important; 
        transform: translateY(-2px);
    }
    @media (max-width: 767px) {
        .admin-navbar { position: relative; }
        .admin-navbar + div { margin-top: 0 !important; }
    }
</style>

<div class="d-none d-md-block" style="margin-top: 76px;"></div>