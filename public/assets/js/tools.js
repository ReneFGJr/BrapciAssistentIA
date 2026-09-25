(() => {
    const root = document.querySelector('[data-generator]');
    if (!root) return;
    const result = document.getElementById('generator-result');
    const status = document.getElementById('generator-status');
    const copy = document.getElementById('generator-copy');
    const groups = {
        upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        lower: 'abcdefghijklmnopqrstuvwxyz',
        digits: '0123456789',
        symbols: '!@#$%&*()-_=+[]{}:;,.?'
    };
    function randomIndex(max) {
        const limit = Math.floor(4294967296 / max) * max;
        const buffer = new Uint32Array(1);
        do { globalThis.crypto.getRandomValues(buffer); } while (buffer[0] >= limit);
        return buffer[0] % max;
    }
    function password() {
        const length = Number(document.getElementById('password-length').value);
        const selected = [...root.querySelectorAll('[data-character-group]:checked')]
            .map(input => groups[input.dataset.characterGroup]);
        if (!selected.length) throw new Error('Selecione pelo menos um tipo de caractere.');
        if (!Number.isInteger(length) || length < 8 || length > 128) {
            throw new Error('Escolha um tamanho entre 8 e 128 caracteres.');
        }
        const pool = selected.join('');
        // Rejection sampling keeps all valid passwords equally likely.
        let value;
        do {
            value = Array.from({ length }, () => pool[randomIndex(pool.length)]).join('');
        } while (!selected.every(group => [...value].some(char => group.includes(char))));
        return value;
    }
    function cpf() {
        let digits;
        do { digits = Array.from({ length: 9 }, () => randomIndex(10)); }
        while (digits.every(value => value === digits[0]));
        for (let size = 9; size <= 10; size++) {
            const sum = digits.reduce((total, digit, index) => total + digit * (size + 1 - index), 0);
            const remainder = sum % 11;
            digits.push(remainder < 2 ? 0 : 11 - remainder);
        }
        return digits.join('');
    }
    let rawCpf = '';
    function formatCpf() {
        result.value = document.getElementById('cpf-formatted').checked
            ? rawCpf.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4') : rawCpf;
    }
    document.getElementById('generator-form').addEventListener('submit', event => {
        event.preventDefault();
        try {
            if (!globalThis.crypto?.getRandomValues) throw new Error('O navegador não oferece geração aleatória segura.');
            if (root.dataset.generator === 'senha') result.value = password();
            else { rawCpf = cpf(); formatCpf(); }
            copy.disabled = false;
            status.textContent = 'Gerado. Use o botão ao lado para copiar.';
        } catch (error) {
            status.textContent = error.message;
        }
    });
    document.getElementById('cpf-formatted')?.addEventListener('change', () => {
        if (rawCpf) formatCpf();
    });
    copy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(result.value);
            status.textContent = 'Copiado para a área de transferência.';
        } catch {
            result.focus();
            result.select();
            status.textContent = 'Resultado selecionado. Use Ctrl+C ou o comando Copiar do dispositivo.';
        }
    });
})();