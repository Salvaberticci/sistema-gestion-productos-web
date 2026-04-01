// scanner.js - Escáner de código de barras con la cámara

document.addEventListener('DOMContentLoaded', () => {
    const scannerResult = document.getElementById('scannerResult');
    const qrReaderDiv = document.getElementById('reader');
    if (!qrReaderDiv) return;

    let html5QrCode = new Html5Qrcode("reader");
    let isScanning = false;

    const beep = (freq = 440, duration = 150) => {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        oscillator.frequency.value = freq;
        oscillator.type = "sine";
        gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.2, audioCtx.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration/1000);
        oscillator.start(audioCtx.currentTime);
        oscillator.stop(audioCtx.currentTime + duration/1000);
    };

    const showProductResult = (p) => {
        const imgHtml = p.img_url 
            ? `<img src="${p.img_url}" style="width:100px; height:100px; object-fit:cover; border-radius:15px; border:3px solid var(--color-accent);">`
            : `<div style="width:100px; height:100px; background:var(--color-surface); border-radius:15px; border:1px solid var(--color-border); display:flex; align-items:center; justify-content:center; font-size:2rem;">📦</div>`;

        scannerResult.innerHTML = `
            <div class="card mt-2" style="animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);">
                <div class="flex items-center gap-3">
                    ${imgHtml}
                    <div class="flex-1 min-width-0">
                        <div class="text-dim mb-1" style="font-size:0.7rem; font-weight:700; letter-spacing:1px;">PRODUCTO DETECTADO</div>
                        <div style="font-size:1.125rem; font-weight:800; color:var(--color-text-main); margin-bottom:4px;">${p.referencia}</div>
                        <div style="font-size:1.5rem; font-weight:800; color:var(--color-accent); line-height:1;">${p.formatted_usd} <span style="font-size:0.8rem; color:var(--color-text-dim);">USD</span></div>
                        <div style="font-size:1.125rem; font-weight:700; color:var(--color-gold);">${p.formatted_bs} <span style="font-size:0.6rem;">BS.</span></div>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <a href="${window.APP_URL}/modules/impresion/index.php?cod=${p.codigop}" class="btn btn-primary flex-1">🖨️ Imprimir Ticket</a>
                    <button class="btn btn-secondary" onclick="document.getElementById('scannerResult').innerHTML = ''">❌ Cerrar</button>
                </div>
            </div>
        `;
        beep(880, 200); // Tono de éxito
    };

    const onScanSuccess = async (decodedText, decodedResult) => {
        if (isScanning) {
            // Pausar temporalmente para evitar scans duplicados instantaneos
            isScanning = false;
            beep(1200, 100);
            
            try {
                const response = await fetch(`${window.APP_URL}/modules/inventario/api_buscar.php?q=${encodeURIComponent(decodedText)}`);
                const products = await response.json();
                
                if (products && products.length > 0) {
                    // Si el codigo coincide exactamente con el primero
                    const p = products.find(prod => prod.codigop === decodedText) || products[0];
                    showProductResult(p);
                } else {
                    showToast("No se encontró producto con código: " + decodedText, "error");
                    beep(200, 400); // Tono de error
                }
            } catch (err) {
                console.error(err);
            }

            setTimeout(() => { isScanning = true; }, 3000); // Reactivar scan tras 3 seg
        }
    };

    const startScanner = () => {
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true
        };

        const configMobile = {
            fps: 15,
            qrbox: (viewWidth, viewHeight) => {
                let size = Math.min(viewWidth, viewHeight) * 0.7;
                return { width: size, height: size };
            },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true
        };

        html5QrCode.start(
            { facingMode: "environment" }, 
            (window.innerWidth < 768 ? configMobile : config), 
            onScanSuccess
        ).then(() => {
            isScanning = true;
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display = 'block';
        }).catch(err => {
            showToast("Error al iniciar cámara: " + err, "error");
        });
    };

    const stopScanner = () => {
        html5QrCode.stop().then(() => {
            isScanning = false;
            document.getElementById('startBtn').style.display = 'block';
            document.getElementById('stopBtn').style.display = 'none';
            qrReaderDiv.innerHTML = '';
        });
    };

    document.getElementById('startBtn').addEventListener('click', startScanner);
    document.getElementById('stopBtn').addEventListener('click', stopScanner);
    
    // Auto start if screen is mobile size? 
    // No, mejor manual para cumplir con politicas de reproduccion de audio/video
});
