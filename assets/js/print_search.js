// print_search.js - Búsqueda para el módulo de impresión

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('printSearch');
    if (!searchInput) return;

    const desktopBody = document.getElementById('printInventoryBody');
    const mobileCards = document.getElementById('mobilePrintCards');

    const renderResults = (products) => {
        desktopBody.innerHTML = '';
        mobileCards.innerHTML = '';

        if (products.length === 0) {
            desktopBody.innerHTML = `<tr><td colspan="4" class="text-center p-3 text-dim">No se encontraron resultados</td></tr>`;
            return;
        }

        products.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    ${p.img_url ? `<img src="${p.img_url}" style="width:32px; height:32px; object-fit:cover; border-radius:5px;">` : '📦'}
                </td>
                <td><span class="fw-bold">${p.referencia}</span></td>
                <td><span class="text-dim">@</span>${p.codigop}</td>
                <td style="text-align:right;">
                    <a href="generar_pdf.php?cod=${p.codigop}" target="_blank" class="btn btn-primary btn-sm">🖨️ Ticket</a>
                </td>
            `;
            desktopBody.appendChild(tr);

            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <div class="product-card-img">
                    ${p.img_url ? `<img src="${p.img_url}" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">` : '📦'}
                </div>
                <div class="product-card-info">
                    <div class="product-card-name">${p.referencia}</div>
                    <div class="product-card-code">Cod: ${p.codigop}</div>
                    <div class="product-card-price" style="text-align:left; font-size:1rem; color:var(--color-gold); font-weight:800;">${p.formatted_bs} <span style="font-size:0.75rem;">BS.</span></div>
                </div>
                <a href="generar_pdf.php?cod=${p.codigop}" target="_blank" class="btn btn-primary btn-sm">🖨️</a>
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
            showToast('Error en la búsqueda', 'error');
        }
    };

    searchInput.addEventListener('input', debounce((e) => {
        performSearch(e.target.value);
    }, 400));

    // Inicializar
    const urlParams = new URLSearchParams(window.location.search);
    const initialCod = urlParams.get('cod');
    if (initialCod) {
        searchInput.value = initialCod;
        performSearch(initialCod);
    } else {
        performSearch('');
    }
});
