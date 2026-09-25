(() => {
    const root = document.querySelector('[data-code-tool]');
    if (!root) return;
    const form = document.getElementById('code-form');
    const input = document.getElementById('code-content');
    const format = document.getElementById('barcode-format');
    const output = document.getElementById('code-result');
    const canvas = document.getElementById('code-canvas');
    const download = document.getElementById('code-download');
    const status = document.getElementById('code-status');
    let revision = 0;
    const clear = () => {
        revision++;
        output.hidden = true;
        download.removeAttribute('href');
        status.textContent = '';
    };
    input.addEventListener('input', clear);
    format?.addEventListener('change', clear);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        clear();
        const current = revision;
        const value = input.value.trim();
        try {
            if (!value) throw new Error('Informe o conteúdo do código.');
            if (root.dataset.codeTool === 'barcode') {
                if (typeof window.JsBarcode !== 'function') throw new Error('Não foi possível carregar o gerador. Recarregue a página.');
                if (format.value === 'EAN13' && !/^\d{12,13}$/.test(value)) throw new Error('EAN-13 exige 12 ou 13 dígitos.');
                if (format.value === 'CODE128' && !/^[\x20-\x7E]{1,80}$/.test(value)) throw new Error('Use até 80 caracteres sem acentos ou quebras de linha.');
                let valid = true;
                window.JsBarcode(canvas, value, {
                    format: format.value, width: 2, height: 100, margin: 24,
                    background: '#ffffff', lineColor: '#000000', displayValue: true,
                    valid: result => { valid = result; }
                });
                if (!valid) throw new Error('Código inválido. Confira o dígito verificador do EAN-13.');
            } else {
                if (!window.QRCode?.toCanvas) throw new Error('Não foi possível carregar o gerador. Recarregue a página.');
                if (value.length > 1500) throw new Error('Use até 1.500 caracteres.');
                await window.QRCode.toCanvas(canvas, value, { errorCorrectionLevel: 'M', margin: 4, scale: 6 });
            }
            if (current !== revision) return;
            download.href = canvas.toDataURL('image/png');
            output.hidden = false;
            status.textContent = 'Código gerado. A imagem está pronta para baixar.';
        } catch (error) {
            if (current !== revision) return;
            status.textContent = /amount of data|too big/i.test(error.message || '')
                ? 'O conteúdo ultrapassa a capacidade do QR Code. Reduza o texto.'
                : (error.message || 'Não foi possível gerar o código. Confira o conteúdo.');
        }
    });
})();