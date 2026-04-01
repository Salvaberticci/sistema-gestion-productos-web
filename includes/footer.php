      </div><!-- .content-wrapper -->
    </main>
</div><!-- .app-layout -->

<!-- Bottom Navigation Mobile -->
<nav class="bottom-nav">
    <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="bottom-nav-item <?= $currentModule === 'dashboard' ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📊</span><span class="bottom-nav-label">Inicio</span>
    </a>
    <a href="<?= APP_URL ?>/modules/inventario/index.php" class="bottom-nav-item <?= $currentModule === 'inventario' ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📦</span><span class="bottom-nav-label">Inventario</span>
    </a>
    <a href="<?= APP_URL ?>/modules/scanner/index.php" class="bottom-nav-item <?= $currentModule === 'scanner' ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📸</span><span class="bottom-nav-label">Escáner</span>
    </a>
    <a href="<?= APP_URL ?>/modules/impresion/index.php" class="bottom-nav-item <?= $currentModule === 'impresion' ? 'active' : '' ?>">
        <span class="bottom-nav-icon">🖨️</span><span class="bottom-nav-label">Imprimir</span>
    </a>
    <a href="<?= APP_URL ?>/modules/consulta/index.php" class="bottom-nav-item <?= $currentModule === 'consulta' ? 'active' : '' ?>">
        <span class="bottom-nav-icon">🔍</span><span class="bottom-nav-label">Consulta</span>
    </a>
</nav>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?php if (isset($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
