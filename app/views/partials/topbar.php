<?php
/**
 * Barra superior
 */
?>
<div class="top-bar">
    <div>
        <h4 class="mb-0"><?= e($title ?? 'Dashboard') ?></h4>
        <small class="text-muted"><?= e($subtitle ?? '') ?></small>
    </div>
    <div class="user-info">
        <?php if (isset($showBalance) && $showBalance): ?>
        <div class="text-end">
            <span class="text-muted">Mi Saldo:</span><br>
            <span class="balance-display <?= $user['balance'] < ($settings['min_balance_alert'] ?? 10) ? 'text-danger' : '' ?>">
                <?= formatMoney($user['balance']) ?>
            </span>
        </div>
        <?php else: ?>
        <div>
            <strong><?= e($user['name']) ?></strong>
            <br><small class="text-muted"><?= $user['role'] === 'admin' ? 'Administrador' : 'Cliente' ?></small>
        </div>
        <?php endif; ?>
        <div class="avatar">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
    </div>
</div>
