// pos.js - Lógica interactiva para el Punto de Venta (Unificado)

let cartItems = [];
let html5QrcodeScanner = null;

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar foco en Cédula
    setTimeout(() => { document.getElementById('c_cedula').focus(); }, 300);

    // Eventos de barra de búsqueda
    const posSearch = document.getElementById('posSearch');
    if (posSearch) {
        posSearch.addEventListener('input', debounce((e) => {
            if (e.target.value.trim().length > 0) {
                buscarProducto(e.target.value);
            } else {
                document.getElementById('searchResults').innerHTML = '<div class="text-center p-3 text-dim">Busque un producto para añadir al carrito</div>';
            }
        }, 300));
        
        posSearch.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProducto(e.target.value.trim(), true);
                e.target.value = '';
            }
        });
    }

    // Cámara Escáner 
    document.getElementById('startScannerBtn')?.addEventListener('click', () => {
        document.getElementById('scannerDiv').style.display = 'block';
        document.getElementById('startScannerBtn').style.display = 'none';
        iniciarCamara();
    });

    document.getElementById('stopScannerBtn')?.addEventListener('click', () => {
        detenerCamara();
    });
    
    // Al pulsar Enter en Cédula, buscar cliente
    document.getElementById('c_cedula')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarCliente();
        }
    });
});

/* ========================================================
   1. GESTION DE CLIENTES
======================================================== */
function buscarCliente() {
    const cedula = document.getElementById('c_cedula').value.trim();
    if (!cedula) return;
    
    fetch(`${window.APP_URL}/modules/ventas/api_cliente.php?cedula=${encodeURIComponent(cedula)}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('c_nombre').value = data.cliente.nombre;
            document.getElementById('c_apellido').value = data.cliente.apellido;
            document.getElementById('c_telefono').value = data.cliente.telefono;
            showToast('Cliente encontrado', 'success');
            document.getElementById('posSearch').focus();
        } else {
            // No existe, limpiar el resto pero dejar cedula
            document.getElementById('c_nombre').value = '';
            document.getElementById('c_apellido').value = '';
            document.getElementById('c_telefono').value = '';
            document.getElementById('c_nombre').focus();
        }
    }).catch(err => console.error(err));
}

function resetVenta() {
    if (cartItems.length > 0) {
        Swal.fire({
            title: '¿Limpiar formulario?',
            text: 'Se perderá el progreso de la orden actual.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f85149',
            cancelButtonColor: '#30363d',
            confirmButtonText: 'Sí, limpiar',
            background: '#0D1117',
            color: '#fff'
        }).then((res) => { if (res.isConfirmed) doReset(); });
    } else {
        doReset();
    }
}

function doReset() {
    cartItems = [];
    renderCart();
    document.getElementById('c_cedula').value = '';
    document.getElementById('c_nombre').value = '';
    document.getElementById('c_apellido').value = '';
    document.getElementById('c_telefono').value = '';
    document.getElementById('posSearch').value = '';
    document.getElementById('searchResults').innerHTML = '<div class="text-center p-3 text-dim">Busque un producto para añadir al carrito</div>';
    detenerCamara();
    document.getElementById('c_cedula').focus();
}

/* ========================================================
   2. BÚSQUEDA Y CARRITO
======================================================== */
function buscarProducto(query, autoSelectFirst = false) {
    // Ya no bloqueamos por cliente, permitimos buscar libremente
    fetch(`${window.APP_URL}/modules/inventario/api_buscar.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(products => {
            const container = document.getElementById('searchResults');
            container.innerHTML = '';
            
            if (products.length === 0) {
                container.innerHTML = `<div class="text-center p-3 text-danger">No se encontraron productos.</div>`;
                return;
            }

            if (autoSelectFirst && products.length > 0) {
                pedirCantidad(products[0]);
                return;
            }

            products.forEach(p => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.style.cursor = 'pointer';
                card.onclick = () => pedirCantidad(p);
                card.innerHTML = `
                    <div class="product-card-info" style="flex:1;">
                        <div class="product-card-name" style="-webkit-line-clamp: 2;">${p.referencia}</div>
                        <div class="product-card-code">Cod: ${p.codigop} • Stock: <span class="${p.exisact <= 0 ? 'text-danger fw-bold' : ''}">${parseFloat(p.exisact).toFixed(0)}</span></div>
                    </div>
                    <div class="product-card-price text-gold" style="flex-shrink:0;">
                        ${p.formatted_bs} <span style="font-size:0.7rem;">BS.</span>
                    </div>
                `;
                container.appendChild(card);
            });
        });
}

function pedirCantidad(product) {
    Swal.fire({
        title: 'Cantidad',
        html: `<b>${product.referencia}</b><br><br>Stock: ${Math.floor(product.exisact)}<br>Precio: <span class="text-gold" style="font-size:1.4rem; font-weight:800;">${product.formatted_bs} <small>BS.</small></span>`,
        input: 'number',
        inputValue: 1,
        inputAttributes: { min: 1, step: 1 },
        showCancelButton: true,
        confirmButtonText: 'Añadir',
        background: '#0D1117',
        color: '#fff'
    }).then(result => {
        if (result.isConfirmed) {
            const qty = parseFloat(result.value);
            if (isNaN(qty) || qty <= 0) return;
            
            if (qty > product.exisact) {
                Swal.fire({
                    title: 'Stock Insuficiente',
                    text: `Hay ${product.exisact} en sistema. ¿Añadir ${qty} de todos modos?`,
                    icon: 'warning',
                    showCancelButton: true,
                    background: '#0D1117',
                    color: '#fff'
                }).then(res => { if (res.isConfirmed) agregarAlCarrito(product, qty); });
            } else {
                agregarAlCarrito(product, qty);
            }
        }
    });
}

function agregarAlCarrito(product, qty) {
    const existingIndex = cartItems.findIndex(i => i.codigop === product.codigop);
    if (existingIndex >= 0) {
        cartItems[existingIndex].cantidad += qty;
    } else {
        const item = {...product, cantidad: qty};
        cartItems.push(item);
    }
    
    document.getElementById('posSearch').value = '';
    document.getElementById('searchResults').innerHTML = '<div class="text-center p-3 text-dim">Busque un producto para añadir al carrito</div>';
    document.getElementById('posSearch').focus();
    
    if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
        // Opcional: no detener si se quiere seguir escaneando rápido
    }
    
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItemsContainer');
    const totalUsdSpan = document.getElementById('cartTotalUSD');
    const totalBsSpan = document.getElementById('cartTotalBS');
    const btnEnviar = document.getElementById('btnEnviarOrden');
    
    container.innerHTML = '';
    let totUsd = 0;
    
    if (cartItems.length === 0) {
        container.innerHTML = '<div class="text-center text-dim mt-3">El carrito está vacío</div>';
        totalUsdSpan.innerText = '$ 0.00';
        totalBsSpan.innerText = 'Bs. 0.00';
        btnEnviar.disabled = true;
        return;
    }
    
    btnEnviar.disabled = false;
    
    cartItems.forEach((item, index) => {
        totUsd += (item.pventa * item.cantidad);
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div style="flex:1;">
                <div class="cart-item-name">${item.referencia}</div>
                <div style="font-size:0.75rem; color:var(--color-text-dim);">Cod: ${item.codigop}</div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <div class="cart-item-qty">${item.cantidad} x</div>
                <div class="cart-item-price" style="color:var(--color-gold);">Bs. ${(item.precio_bs * item.cantidad).toLocaleString('es-VE', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                <div style="cursor:pointer; color:var(--color-danger); font-size:1.1rem;" onclick="removerItem(${index})">🗑️</div>
            </div>
        `;
        container.appendChild(div);
    });
    
    const rate = cartItems[0] ? (cartItems[0].precio_bs / cartItems[0].pventa) : 1;
    totalBsSpan.innerText = `Bs. ${(totUsd * rate).toLocaleString('es-VE', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
    if (totalUsdSpan) totalUsdSpan.innerText = `$ ${totUsd.toFixed(2)}`;
}

function removerItem(index) {
    cartItems.splice(index, 1);
    renderCart();
}

/* ========================================================
   3. ENVÍO DE ORDEN
======================================================== */
async function preEnviarOrden() {
    const cedula = document.getElementById('c_cedula').value.trim();
    const nombre = document.getElementById('c_nombre').value.trim();
    const apellido = document.getElementById('c_apellido').value.trim();
    const telefono = document.getElementById('c_telefono').value.trim();

    if (!cedula || !nombre || !apellido || !telefono) {
        Swal.fire('Datos incompletos', 'Por favor complete todos los datos del cliente.', 'warning');
        return;
    }

    if (cartItems.length === 0) {
        Swal.fire('Carrito vacío', 'Añada al menos un producto.', 'warning');
        return;
    }

    // Primero guardamos/actualizamos al cliente
    try {
        const resCli = await fetch(`${window.APP_URL}/modules/ventas/api_cliente.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cedula, nombre, apellido, telefono})
        });
        const dataCli = await resCli.json();

        if (dataCli.success) {
            enviarOrden(dataCli.cliente.id);
        } else {
            Swal.fire('Error Cliente', dataCli.error, 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo registrar al cliente.', 'error');
    }
}

function enviarOrden(cliente_id) {
    const btn = document.getElementById('btnEnviarOrden');
    btn.disabled = true;
    btn.innerText = '⏳ Procesando...';

    const payload = {
        cliente_id: cliente_id,
        items: cartItems.map(i => ({
            codigop: i.codigop,
            cantidad: i.cantidad,
            pventa: i.pventa
        }))
    };

    fetch(`${window.APP_URL}/modules/ventas/api_orden.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire({
                title: '¡Orden Enviada!',
                text: `La orden #${res.orden_id} está pendiente por aprobación.`,
                icon: 'success',
                background: '#0D1117',
                color: '#fff'
            }).then(() => doReset());
        } else {
            Swal.fire('Error Orden', res.error, 'error');
            btn.disabled = false;
            btn.innerText = '✅ Procesar y Enviar Orden';
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerText = '✅ Procesar y Enviar Orden';
    });
}

/* ========================================================
   4. CONTROL DE CAMARA
======================================================== */
function iniciarCamara() {
    if (!html5QrcodeScanner) {
        html5QrcodeScanner = new Html5Qrcode("reader");
    }
    
    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            let cameraId = devices[0].id;
            for(let i=0; i<devices.length; i++){
                if(devices[i].label.toLowerCase().includes('back') || devices[i].label.toLowerCase().includes('trasera')) {
                    cameraId = devices[i].id;
                    break;
                }
            }
            
            html5QrcodeScanner.start(
                cameraId,
                { fps: 10, qrbox: {width: 250, height: 150} },
                (decodedText) => {
                    document.getElementById('posSearch').value = decodedText;
                    buscarProducto(decodedText, true);
                }
            ).catch(err => console.error(err));
        }
    }).catch(err => Swal.fire('Error Cámara', 'No se detectaron cámaras.', 'error'));
}

function detenerCamara() {
    if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
        html5QrcodeScanner.stop().then(() => {
            document.getElementById('scannerDiv').style.display = 'none';
            document.getElementById('startScannerBtn').style.display = 'block';
        });
    } else {
        document.getElementById('scannerDiv').style.display = 'none';
        document.getElementById('startScannerBtn').style.display = 'block';
    }
}
