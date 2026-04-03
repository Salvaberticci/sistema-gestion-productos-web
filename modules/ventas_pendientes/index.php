<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$pageTitle = 'Gestión de Órdenes Pendientes';
$currentModule = 'ventas_pendientes';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">🔍 Órdenes Pendientes</h2>
</div>

<!-- Search Bar -->
<div class="search-bar mb-4">
    <input type="text" id="orderSearch" class="form-input" placeholder="Buscar por nombre, cédula o #ID..." autocomplete="off">
</div>

<!-- Results Area -->
<div class="card p-0" id="ordersContainer">
    <!-- Vista Desktop (Tabla) -->
    <div class="table-responsive desktop-only">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th style="text-align:right;">Acción</th>
                </tr>
            </thead>
            <tbody id="desktopOrdersBody">
                <!-- AJAX Load -->
                <tr><td colspan="6" class="text-center p-4">Escribe para empezar a buscar...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Vista Móvil (Tarjetas) -->
    <div class="product-cards p-3 mobile-only" id="mobileOrdersCards">
        <!-- AJAX Load -->
        <p class="text-center text-dim">Escribe para empezar a buscar...</p>
    </div>
</div>

<style>
    @media (max-width: 767px) {
        .desktop-only { display: none !important; }
        .mobile-only { display: block !important; }
    }
    @media (min-width: 768px) {
        .mobile-only { display: none !important; }
        .desktop-only { display: block !important; }
    }

    .order-card-premium {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        transition: var(--transition);
    }
    .order-card-premium:hover {
        border-color: var(--color-accent);
        background: rgba(255, 255, 255, 0.05);
    }
</style>

<script>
window.APP_URL = '<?= APP_URL ?>';

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('orderSearch');
    const desktopBody = document.getElementById('desktopOrdersBody');
    const mobileContainer = document.getElementById('mobileOrdersCards');

    // Carga inicial
    performSearch('');

    let timeout = null;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            performSearch(e.target.value);
        }, 300);
    });

    function performSearch(query) {
        fetch(`${window.APP_URL}/modules/ventas_pendientes/api_buscar.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) throw new Error(res.error);
                
                renderResults(res.data);
            })
            .catch(err => {
                console.error(err);
                desktopBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger p-4">Error: ${err.message}</td></tr>`;
            });
    }

    function renderResults(orders) {
        if (orders.length === 0) {
            const emptyMsg = '<tr><td colspan="6" class="text-center p-4 text-dim">No se encontraron órdenes pendientes.</td></tr>';
            desktopBody.innerHTML = emptyMsg;
            mobileContainer.innerHTML = '<p class="text-center text-dim p-4">No hay resultados.</p>';
            return;
        }

        let desktopHtml = '';
        let mobileHtml = '';

        orders.forEach(o => {
            // Desktop
            desktopHtml += `
                <tr>
                    <td><span class="text-dim">#</span>${o.id}</td>
                    <td><span class="fw-bold">${o.cliente_full}</span></td>
                    <td><small class="text-dim">${o.cedula}</small></td>
                    <td>
                        <div class="text-accent fw-bold">${o.total_usd_fmt}</div>
                        <div class="text-dim" style="font-size:0.7rem;">${o.total_bs_fmt}</div>
                    </td>
                    <td><small>${o.fecha_fmt}</small></td>
                    <td style="text-align:right;">
                        <button onclick="viewOrder(${o.id})" class="btn btn-primary btn-sm">👁️ Ver Detalle</button>
                    </td>
                </tr>
            `;

            // Mobile
            mobileHtml += `
                <div class="order-card-premium" onclick="viewOrder(${o.id})">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div style="font-size:0.7rem; font-weight:800; color:var(--color-text-dim);">ORDEN #${o.id}</div>
                            <div style="font-weight:800; font-size:1rem;">${o.cliente_full}</div>
                            <div style="font-size:0.7rem; opacity:0.6;">V.I.P CLIENT - ${o.cedula}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:900; color:var(--color-accent); font-size:1.1rem;">${o.total_usd_fmt}</div>
                            <div style="font-size:0.65rem; color:var(--color-gold); font-weight:700;">${o.total_bs_fmt}</div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-3 pt-2" style="border-top:1px solid rgba(255,255,255,0.05);">
                        <span style="font-size:0.65rem; color:var(--color-text-dim);">${o.fecha_fmt}</span>
                        <span style="font-size:0.6rem; background:var(--color-danger); color:#fff; padding:2px 8px; border-radius:4px; font-weight:bold;">PENDIENTE</span>
                    </div>
                </div>
            `;
        });

        desktopBody.innerHTML = desktopHtml;
        mobileContainer.innerHTML = mobileHtml;
    }

    // Refresco Automático cada 5 segundos
    setInterval(() => {
        // Solo refrescar si el buscador está vacío para no interrumpir al admin
        if (searchInput.value.trim() === "") {
            performSearch("");
        }
    }, 5000);
});

// Reutilizar lógica de aprobación del Dashboard (Referenciando las APIs del Dashboard)
function viewOrder(orderId) {
    Swal.fire({
        title: 'Cargando orden...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
            fetch(`${window.APP_URL}/modules/dashboard/api_orden_detalle.php?id=${orderId}`)
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                    return res.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            renderOrderDetail(data, orderId);
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    } catch (e) {
                        console.error('La respuesta no es un JSON válido:', text);
                        Swal.fire('Error de Sistema', 'El servidor devolvió una respuesta no válida. Revisa la consola para más detalles.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error en fetch:', err);
                    Swal.fire('Error de Conexión', err.message, 'error');
                });
        }
    });
}

function renderOrderDetail(data, orderId) {
    let html = `
        <div style="text-align:left;">
            <p><b>Cliente:</b> ${data.orden.nombre} ${data.orden.apellido}</p>
            <p><b>Fecha:</b> ${data.orden.fecha}</p>
            <hr style="border-color:var(--color-border); margin: 15px 0;">
            <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid var(--color-border);">
                        <th style="text-align:left; padding:8px 0;">Producto</th>
                        <th style="text-align:center;">Cant</th>
                        <th style="text-align:right;">Precio</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.items.map(i => `
                        <tr style="border-bottom:1px solid var(--color-border);">
                            <td style="padding:10px 0;">${i.referencia}<br><small class="text-dim">#${i.producto_cod}</small></td>
                            <td style="text-align:center;">${i.cantidad}</td>
                            <td style="text-align:right;">$${parseFloat(i.precio_unitario_usd).toFixed(2)}</td>
                            <td style="text-align:right; font-weight:bold;">$${parseFloat(i.subtotal_usd).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div style="margin-top:20px; text-align:right;">
                <div style="font-size:1.4rem; font-weight:900; color:var(--color-success);">$${parseFloat(data.orden.total_usd).toFixed(2)}</div>
                <div style="font-size:1rem; font-weight:700; color:var(--color-gold);">Bs. ${parseFloat(data.orden.total_bs).toFixed(2)}</div>
            </div>
        </div>
    `;

    Swal.fire({
        title: `Orden #` + orderId,
        html: html,
        width: '600px',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '✅ Aprobar Venta',
        denyButtonText: '❌ Rechazar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: 'var(--color-success)',
        denyButtonColor: 'var(--color-danger)',
        background: '#0D1117',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            processOrder(orderId, 'aprobar');
        } else if (result.isDenied) {
            processOrder(orderId, 'rechazar');
        }
    });
}

function processOrder(orderId, action) {
    Swal.fire({
        title: 'Procesando...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
            fetch(`${window.APP_URL}/modules/dashboard/api_orden_accion.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: orderId, accion: action})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: action === 'aprobar' ? '¡Venta Aprobada!' : 'Orden Rechazada',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
