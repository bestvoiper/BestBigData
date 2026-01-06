<?php
/**
 * Sidebar del panel de administración
 */
?>
<div class="sidebar">
    <div class="logo">
        <i class="bi bi-telephone-fill"></i>
        <h4>DetectNUM</h4>
        <small class="text-white-50">Panel Admin</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= url('admin') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>" href="<?= url('admin/users') ?>">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'transactions' ? 'active' : '' ?>" href="<?= url('admin/transactions') ?>">
            <i class="bi bi-currency-dollar"></i> Transacciones
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'searches' ? 'active' : '' ?>" href="<?= url('admin/searches') ?>">
            <i class="bi bi-search"></i> Búsquedas
        </a>
        <a class="nav-link <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>" href="<?= url('admin/settings') ?>">
            <i class="bi bi-gear"></i> Configuración
        </a>
        <a class="nav-link text-danger" href="<?= url('auth/logout') ?>">
            <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
        </a>
    </nav>
</div>
