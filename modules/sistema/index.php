<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Sincronización Avanzada';
$currentModule = 'sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<style>
/* Estilos Premium para Sincronización */
.sync-container {
    max-width: 1000px;
    margin: 0 auto;
    padding-bottom: 40px;
}

.sync-card {
    background: rgba(22, 27, 34, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(48, 54, 61, 0.8);
    border-radius: 20px;
    padding: 40px !important; /* Más aire interno en escritorio */
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
}

.sync-card:hover {
    border-color: var(--color-accent);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.sync-section-title {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.sync-icon-box {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    font-size: 24px;
    background: rgba(88, 166, 255, 0.1);
    color: var(--color-accent);
}

.feature-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: rgba(0,0,0,0.2);
    border-radius: 12px;
    border: 1px solid transparent;
    transition: var(--transition);
}

.feature-item:hover {
    border-color: rgba(209, 160, 84, 0.3);
}

.feature-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.feature-text {
    font-size: 0.875rem;
    line-height: 1.4;
}

/* Dropzone Styles */
.premium-dropzone {
    border: 2px dashed var(--color-border);
    background: rgba(13, 17, 23, 0.4);
    border-radius: 16px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.premium-dropzone:hover, .premium-dropzone.active {
    border-color: var(--color-success);
    background: rgba(35, 134, 54, 0.05);
}

.premium-dropzone .dz-pulse {
    position: absolute;
    inset: 0;
    pointer-events: none;
    border-radius: 16px;
    animation: dzPulse 2s infinite;
}

@keyframes dzPulse {
    0% { box-shadow: 0 0 0 0 rgba(35, 134, 54, 0.2); }
    70% { box-shadow: 0 0 0 10px rgba(35, 134, 54, 0); }
    100% { box-shadow: 0 0 0 0 rgba(35, 134, 54, 0); }
}

.file-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 20px;
    font-size: 0.75rem;
    margin: 4px;
}

/* Mobile Adjustments */
@media (max-width: 991px) {
    .sync-card {
        padding: 25px !important;
    }
}

@media (max-width: 767px) {
    .sync-grid {
        grid-template-columns: 1fr !important;
        gap: 25px !important; /* Más separación entre cards en móvil también */
    }
    
    .sync-card {
        padding: 20px !important;
    }

    .sync-icon-box {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .page-title {
        font-size: 1.35rem;
    }
    
    .feature-item {
        padding: 15px !important;
        gap: 15px !important;
    }

    .feature-text b {
        display: block;
        margin-bottom: 4px;
        color: #fff;
    }

    .feature-text {
        font-size: 0.9rem;
    }
}
</style>

<div class="sync-container">
    <div class="page-header mt-3">
        <div>
            <h2 class="page-title">🔄 Sincronización Avanzada</h2>
            <p class="text-dim">Mantenimiento masivo de datos y multimedia del Rebusque.</p>
        </div>
    </div>

    <!-- Notificaciones -->
    <?php if (isset($_GET['status'])): ?>
        <div class="card mb-3" style="border-left: 5px solid <?= $_GET['status'] == 'success' ? 'var(--color-success)' : 'var(--color-danger)' ?>; background: rgba(0,0,0,0.3); border-radius: 15px;">
            <div class="p-3">
                <div class="flex items-center gap-3">
                    <span class="text-3xl"><?= $_GET['status'] == 'success' ? '✅' : '❌' ?></span>
                    <div>
                        <h4 class="font-bold <?= $_GET['status'] == 'success' ? 'text-success' : 'text-danger' ?>">
                            <?= htmlspecialchars($_GET['msg']) ?>
                        </h4>
                        <?php if (isset($_GET['detail'])): ?>
                            <p class="text-dim text-sm mt-1"><?= htmlspecialchars($_GET['detail']) ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="index.php" class="btn btn-secondary btn-sm ml-auto" style="min-width: 40px !important; padding: 0 10px;">×</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 sync-grid">
        <!-- SECCIÓN DE EXPORTACIÓN Y BACKUP -->
        <div class="sync-card p-6">
            <div class="sync-section-title">
                <div class="sync-icon-box" style="background: rgba(88, 166, 255, 0.1); color: #58A6FF;">📤</div>
                <div>
                    <h3 class="text-lg font-bold">Exportación y Respaltos</h3>
                    <p class="text-dim text-xs">Extrae tus datos para uso local.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-dark-lighter/50 p-5 rounded-xl border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-bold text-accent">📄 INVENTARIO EXCEL</span>
                        <span class="text-[10px] bg-accent/20 text-accent px-2 py-0.5 rounded">.XLSX</span>
                    </div>
                    <p class="text-xs text-dim mb-4 leading-relaxed">Exporta la base de datos completa con el formato compatible para el sistema Desktop.</p>
                    <a href="api_exportar_excel.php" class="btn btn-primary btn-block text-sm" style="height: 52px; font-weight: 800;">
                        Descargar Base de Datos
                    </a>
                </div>

                <div class="bg-dark-lighter/50 p-5 rounded-xl border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-bold text-success">📦 MULTIMEDIA ZIP</span>
                        <span class="text-[10px] bg-success/20 text-success px-2 py-0.5 rounded">.ZIP</span>
                    </div>
                    <p class="text-xs text-dim mb-4 leading-relaxed">Respaldo masivo de fotos del servidor (renombradas por código de barras).</p>
                    <a href="api_exportar_fotos.php" class="btn btn-success btn-block text-sm" style="height: 52px; font-weight: 800;">
                        Respaldar Imágenes
                    </a>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE IMPORTACIÓN INTELIGENTE -->
        <div class="sync-card p-6">
            <div class="sync-section-title">
                <div class="sync-icon-box" style="background: rgba(209, 160, 84, 0.1); color: #D1A054;">📥</div>
                <div>
                    <h3 class="text-lg font-bold">Importación Inteligente</h3>
                    <p class="text-dim text-xs">Actualización masiva de inventario.</p>
                </div>
            </div>

            <div class="feature-list mb-6">
                <div class="feature-item">
                    <span class="feature-icon">✅</span>
                    <div class="feature-text"><b>Smart Sync:</b> Actualiza stock, precios y nombres al instante.</div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🛡️</span>
                    <div class="feature-text"><b>Filtro de Datos:</b> No borra tus fotos si la columna está vacía.</div>
                </div>
            </div>

            <form action="api_importar_excel.php" method="POST" enctype="multipart/form-data" id="importForm" class="space-y-4">
                <div>
                    <label class="form-label text-xs uppercase tracking-widest opacity-70 mb-2 block">Archivo Excel Master</label>
                    <div class="relative">
                        <input type="file" name="excel_file" id="excel_file" class="hidden" accept=".xlsx, .xls" required onchange="updateFileName()">
                        <label for="excel_file" class="form-input flex items-center justify-between cursor-pointer hover:border-accent transition-all bg-surface/50" style="height: 55px;">
                            <span id="fileName" class="text-dim truncate text-sm">Selecciona tu archivo .xlsx</span>
                            <span class="text-accent text-xs font-bold px-3 py-1 border border-accent/30 rounded-lg">BUSCAR</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold btn-block font-black tracking-widest text-sm" id="btnImport" style="height: 55px;">
                    <span id="importText">🚀 EJECUTAR IMPORTACIÓN</span>
                </button>
            </form>
        </div>

        <!-- CARGA MASIVA DE IMÁGENES -->
        <div class="sync-card p-6 md:col-span-2">
            <div class="sync-section-title">
                <div class="sync-icon-box" style="background: rgba(35, 134, 54, 0.1); color: #2ea043;">📸</div>
                <div>
                    <h3 class="text-lg font-bold">Carga Masiva de Fotos</h3>
                    <p class="text-dim text-xs">Vinculación instantánea por código de barras.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1">
                    <div class="bg-primary/5 p-5 rounded-xl border border-primary/20 flex flex-col gap-4 h-full">
                        <div class="flex items-start gap-3">
                            <span class="text-xl">💡</span>
                            <p class="text-xs text-dim leading-relaxed">
                                Nombra tus archivos como el código del producto (ej: <code class="text-accent">750100.jpg</code>).
                            </p>
                        </div>
                        <div class="mt-auto pt-4 border-t border-border/50">
                            <span class="text-[10px] text-dim block uppercase mb-2 font-bold tracking-wider">Formatos Admitidos</span>
                            <div class="flex flex-wrap gap-2">
                                <span class="bg-dark/50 border border-border px-2 py-1 rounded-md text-[10px] font-bold text-white">JPG</span>
                                <span class="bg-dark/50 border border-border px-2 py-1 rounded-md text-[10px] font-bold text-white">PNG</span>
                                <span class="bg-dark/50 border border-border px-2 py-1 rounded-md text-[10px] font-bold text-white">WEBP</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <form action="api_upload_bulk_images.php" method="POST" enctype="multipart/form-data" id="photoForm">
                        <input type="file" name="fotos[]" id="fotoInput" class="hidden" multiple accept="image/*" onchange="handleFiles(this.files)">
                        <div id="dropzone" class="premium-dropzone">
                            <div class="dz-pulse"></div>
                            <div id="dropzoneContent">
                                <div class="text-5xl mb-4">🎨</div>
                                <h4 class="text-white font-black text-sm mb-1 uppercase tracking-tight">Zona de Selección de Medios</h4>
                                <p class="text-dim text-xs">Arrastra tus fotos o haz clic para buscarlas</p>
                            </div>
                            <div id="filePreview" class="hidden mt-6 text-center">
                                <div class="bg-success/10 text-success text-xs font-bold py-2 px-4 rounded-full inline-block mb-4" id="fileCountText"></div>
                                <div id="fileListNames" class="flex flex-wrap justify-center gap-2"></div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-block mt-4 hidden items-center justify-center gap-2 group" id="btnUploadPhotos">
                            <span class="group-hover:rotate-12 transition-transform">📤</span> SUBIR E INTERCONECTAR
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Procesamiento -->
<div id="loadingOverlay" class="fixed inset-0 bg-black/90 backdrop-blur-md z-[9999] hidden flex-col items-center justify-center gap-6">
    <div class="relative">
        <div class="w-20 h-20 border-4 border-accent/20 border-t-accent rounded-full animate-spin"></div>
        <div class="absolute inset-0 flex items-center justify-center text-xl">⚡</div>
    </div>
    <div class="text-center">
        <div class="text-2xl font-black text-white mb-2" id="loadingText">TRANSFIRIENDO DATOS</div>
        <div class="text-dim text-sm max-w-xs mx-auto">Esta operación puede tardar unos segundos dependiendo del volumen. Por favor, mantén esta ventana abierta.</div>
    </div>
</div>

<script>
function updateFileName() {
    const input = document.getElementById('excel_file');
    const fileName = document.getElementById('fileName');
    if (input.files.length > 0) {
        fileName.textContent = input.files[0].name;
        fileName.classList.remove('text-dim');
        fileName.classList.add('text-white');
    }
}

document.getElementById('importForm').onsubmit = function() {
    showLoading('SINCRONIZANDO INVENTARIO');
    document.getElementById('btnImport').disabled = true;
};

document.getElementById('photoForm').onsubmit = function() {
    showLoading('PROCESANDO MULTIMEDIA');
    document.getElementById('btnUploadPhotos').disabled = true;
};

function showLoading(text) {
    document.getElementById('loadingText').textContent = text;
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('loadingOverlay').classList.add('flex');
}

// Interacción Dropzone
const dropzone = document.getElementById('dropzone');
const fotoInput = document.getElementById('fotoInput');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

dropzone.ondragover = () => dropzone.classList.add('active');
dropzone.ondragleave = () => dropzone.classList.remove('active');
dropzone.ondrop = (e) => {
    dropzone.classList.remove('active');
    fotoInput.files = e.dataTransfer.files;
    handleFiles(fotoInput.files);
};
dropzone.onclick = () => fotoInput.click();

function handleFiles(files) {
    const preview = document.getElementById('filePreview');
    const list = document.getElementById('fileListNames');
    const countText = document.getElementById('fileCountText');
    const btn = document.getElementById('btnUploadPhotos');
    const content = document.getElementById('dropzoneContent');
    
    if (files.length > 0) {
        content.classList.add('hidden');
        preview.classList.remove('hidden');
        btn.classList.remove('hidden');
        btn.classList.add('flex');
        countText.textContent = `${files.length} IMÁGENES LISTAS`;
        list.innerHTML = '';
        Array.from(files).slice(0, 15).forEach(file => {
            const span = document.createElement('span');
            span.className = 'file-pill';
            span.textContent = file.name;
            list.appendChild(span);
        });
        if (files.length > 15) {
            const span = document.createElement('span');
            span.className = 'file-pill opacity-50';
            span.textContent = `+${files.length - 15} más...`;
            list.appendChild(span);
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
