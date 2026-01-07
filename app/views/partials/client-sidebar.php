<?php
/**
 * Sidebar del panel de cliente
 */
?>
<div class="sidebar">
    <div class="logo">
        <i class="bi bi-telephone-fill"></i>
        <h4>BestBigData</h4>
        <small class="text-white-50">Panel Cliente</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= url('client') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'search' ? 'active' : '' ?>" href="<?= url('client/search') ?>">
            <i class="bi bi-search"></i> Buscar Número
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'bulk-search' ? 'active' : '' ?>" href="<?= url('client/bulkSearch') ?>">
            <i class="bi bi-file-earmark-spreadsheet"></i> Búsqueda Masiva
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'history' ? 'active' : '' ?>" href="<?= url('client/history') ?>">
            <i class="bi bi-clock-history"></i> Historial
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>" href="<?= url('client/profile') ?>">
            <i class="bi bi-person"></i> Mi Perfil
        </a>
        <a class="nav-link text-danger" href="<?= url('auth/logout') ?>">
            <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
        </a>
    </nav>
</div>
