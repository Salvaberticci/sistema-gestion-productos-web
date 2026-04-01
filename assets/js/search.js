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
            desktopBody.innerHTML = `<tr><td colspan="5" class="text-center p-3 text-dim">No se encontraron resultados</td></tr>`;
            return;
        }

        products.forEach(p => {
            // Render Desktop Table Row
            const imgHtml = p.img_url 
                ? `<img src="${p.img_url}" style="width:36px; height:36px; object-fit:cover; border-radius:5px;">`
                : `<div style="width:36px; height:36px; background:var(--color-surface); border-radius:5px; display:flex; align-items:center; justify-content:center; font-size:0.75rem;">📦</div>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${imgHtml}</td>
                <td><span class="fw-bold">${p.referencia}</span></td>
                <td><span class="text-dim">#</span>${p.codigop}</td>
                <td class="${p.exisact <= 5 ? 'text-danger fw-bold' : ''}">${parseFloat(p.exisact).toFixed(0)}</td>
                <td class="text-accent fw-extra">${p.formatted_usd} <span class="text-dim" style="font-size:0.7rem;">/ ${p.formatted_bs}</span></td>
                <td style="text-align:right;">
                    <div class="flex justify-end gap-1">
                        <a href="editar.php?id=${p.codigop}" class="btn btn-secondary btn-sm">✏️</a>
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
            showToast('Error de conexión al buscar', 'error');
        }
    };

    searchInput.addEventListener('input', debounce((e) => {
        performSearch(e.target.value);
    }, 400));

    // Inicializar búsqueda vacía
    performSearch('');
});
