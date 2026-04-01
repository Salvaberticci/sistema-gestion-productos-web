// search.js - Búsqueda en vivo para el inventario

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('inventorySearch');
    if (!searchInput) return;

    const desktopBody = document.getElementById('desktopInventoryBody');
    const mobileCards = document.getElementById('mobileProductCards');

    const renderResults = (products) => {
        desktopBody.innerHTML = '';
        mobileCards.innerHTML = '';

        if (products.length === 0) {
            const empty = `<div class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <p class="empty-state-text">No se encontraron productos.</p>
            </div>`;
            mobileCards.innerHTML = empty;
            desktopBody.innerHTML = `<tr><td colspan="6" class="text-center p-3 text-dim">No se encontraron resultados</td></tr>`;
            return;
        }

        products.forEach(p => {
            // Render Desktop Table Row
            const imgHtml = p.img_url 
                ? `<img src="${p.img_url}" style="width:36px; height:36px; object-fit:cover; border-radius:5px;">`
                : `<div style="width:36px; height:36px; background:var(--color-surface); border-radius:5px; display:flex; align-items:center; justify-content:center; font-size:0.75rem;">📦</div>`;

            const tr = document.createElement('tr');
            tr.title = "Doble clic para editar";
            tr.ondblclick = () => window.location.href = `editar.php?id=${p.codigop}`;
            tr.innerHTML = `
                <td>${imgHtml}</td>
                <td><span class="fw-bold">${p.referencia}</span></td>
                <td><span class="text-dim">#</span>${p.codigop}</td>
                <td class="${p.exisact <= 5 ? 'text-danger fw-bold' : ''}">${parseFloat(p.exisact).toFixed(0)}</td>
                <td class="text-accent fw-extra">${p.formatted_usd} <span class="text-dim" style="font-size:0.7rem;">/ ${p.formatted_bs}</span></td>
                <td style="text-align:right;">
                    <div class="flex justify-end gap-1">
                        <a href="editar.php?id=${p.codigop}" class="btn btn-secondary btn-sm" title="Editar">✏️</a>
                        <button onclick="deleteProduct('${p.codigop}', '${p.referencia.replace(/'/g, "\\'")}')" class="btn btn-delete btn-sm" title="Eliminar">🗑️</button>
                    </div>
                </td>
            `;
            desktopBody.appendChild(tr);

            // Render Mobile Card
            const card = document.createElement('div');
            card.className = 'product-card';
            card.onclick = () => window.location.href = `editar.php?id=${p.codigop}`;
            card.innerHTML = `
                <div class="product-card-img">
                    ${p.img_url ? `<img src="${p.img_url}" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">` : '📦'}
                </div>
                <div class="product-card-info">
                    <div class="product-card-name">${p.referencia}</div>
                    <div class="product-card-code">Cod: ${p.codigop} • Stock: <span class="${p.exisact <= 5 ? 'text-danger fw-bold' : ''}">${parseFloat(p.exisact).toFixed(0)}</span></div>
                </div>
                <div class="product-card-price">
                    ${p.formatted_usd}
                    <div class="product-card-stock">${p.formatted_bs}</div>
                </div>
            `;
            mobileCards.appendChild(card);
        });
    };

    const performSearch = async (query = '') => {
        try {
            const response = await fetch(`${window.APP_URL}/modules/inventario/api_buscar.php?q=${encodeURIComponent(query)}`);
            const products = await response.json();
            renderResults(products);
        } catch (error) {
            console.error('Error in search:', error);
            // SweetAlert fallback defined globally
        }
    };

    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    };

    searchInput.addEventListener('input', debounce((e) => {
        performSearch(e.target.value);
    }, 400));

    // Inicializar búsqueda vacía
    performSearch('');
});

/**
 * Función global para eliminar productos con SweetAlert2
 */
window.deleteProduct = function(id, name) {
    Swal.fire({
        title: '¿Eliminar producto?',
        html: `¿Seguro que deseas eliminar <b>${name}</b>?<br><small style="color:var(--color-text-dim)">Esta acción no se puede deshacer.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f85149',
        cancelButtonColor: '#30363d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: '#0D1117',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${window.APP_URL}/modules/inventario/eliminar.php?id=${id}&ajax=1`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'El producto ha sido borrado.',
                            icon: 'success',
                            background: '#0D1117',
                            color: '#ffffff',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        // Recargar la tabla
                        const searchInput = document.getElementById('inventorySearch');
                        if (searchInput) {
                            const event = new Event('input');
                            searchInput.dispatchEvent(event);
                        }
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'No se pudo eliminar el producto.',
                            icon: 'error',
                            background: '#0D1117',
                            color: '#ffffff'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Hubo un problema con la red.', 'error');
                });
        }
    });
};
