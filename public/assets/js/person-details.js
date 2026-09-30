(() => {
    document.querySelectorAll('[data-copy-email]').forEach((button) => {
        button.addEventListener('click', async () => {
            const status = document.getElementById('person-copy-status');
            try {
                await navigator.clipboard.writeText(button.dataset.copyEmail);
                button.title = 'E-mail copiado';
                window.setOperationStatus('E-mail copiado para a área de transferência.', 'success');
            } catch {
                button.title = 'Não foi possível copiar. Selecione e copie o e-mail.';
                window.setOperationStatus(button.title, 'error');

            }
        });
    });
})();
(() => {
    const form = document.querySelector('[data-photo-form]');
    if (!form) return;
    const input = form.querySelector('input[type="file"]');
    const button = form.querySelector('[data-photo-picker]');
    const status = document.querySelector('[data-photo-status]');
    button.addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            window.setOperationStatus('Selecione uma imagem JPG, PNG ou WebP de até 5 MB.', 'error');
            input.value = '';
            return;
        }
        window.setOperationStatus('Enviando fotografia…');
        button.disabled = true;
        form.requestSubmit();
    });
})();