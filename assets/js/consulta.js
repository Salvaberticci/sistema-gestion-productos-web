// consulta.js - Lógica de la pantalla de consulta pública

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('publicSearch');
    const resultDiv = document.getElementById('consultaResult');
    if (!searchInput) return;

    let timeoutId = null;

    const showLoading = () => {
        resultDiv.innerHTML = `<div class="empty-state"><div class="empty-state-icon">⏳</div><p>Buscando...</p></div>`;
    };

    const showDefault = () => {
        resultDiv.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon" style="font-size:4rem; animation: pulse 2s infinite;">🔍</div>
                <p class="empty-state-text" style="font-size:1.125rem;">Esperando escaneo de producto...</p>
            </div>`;
    };

    const renderResult = (p) => {
        resultDiv.innerHTML = `
            <div class="result-card">
                ${p.img_url ? `<img src="${p.img_url}" class="result-img">` : `<div class="result-img flex items-center justify-center" style="background:var(--color-surface); margin:0 auto 20px; font-size:4rem;">📦</div>`}
                <div class="result-name">${p.referencia}</div>
                <div class="result-price-bs">${p.formatted_bs} <span style="font-size:1.5rem;">BS.</span></div>
                <div class="result-price-usd">Ref: ${p.formatted_usd} USD</div>
            </div>
        `;
        
        // Auto-limpiar después de 15 segundos
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            showDefault();
            searchInput.value = '';
            searchInput.focus();
        }, 15000);
    };

    searchInput.addEventListener('input', debounce(async (e) => {
        const val = e.target.value.trim();
        if (val.length < 3) return;

        showLoading();
        try {
            const response = await fetch(`${window.APP_URL}/modules/inventario/api_buscar.php?q=${encodeURIComponent(val)}`);
            const products = await response.json();
            
            if (products && products.length > 0) {
                // Priorizar coincidencia exacta
                const p = products.find(prod => prod.codigop === val) || products[0];
                renderResult(p);
            } else {
                resultDiv.innerHTML = `<div class="result-card" style="border-color:var(--color-danger);">
                    <div style="font-size:4rem; margin-bottom:20px;">❌</div>
                    <div class="result-name" style="color:var(--color-danger);">Producto no encontrado</div>
                    <div class="text-dim">Por favor consulta con un empleado</div>
                </div>`;
                setTimeout(showDefault, 5000);
            }
        } catch (err) {
            console.error(err);
            showToast("Error de conexión", "error");
        }
    }, 500));

    // Forzar foco constante
    document.addEventListener('click', () => searchInput.focus());
});
