(() => {
    function formatCpf(value) {
        return value.replace(/\D/g, '').slice(0, 11)
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3}\.\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3}\.\d{3}\.\d{3})(\d)/, '$1-$2');
    }

    document.querySelectorAll('[data-cpf-mask]').forEach((input) => {
        input.value = formatCpf(input.value);
        input.addEventListener('input', () => {
            const cursor = input.selectionStart ?? input.value.length;
            const before = input.value.slice(0, cursor).replace(/\D/g, '').length;
            input.value = formatCpf(input.value);
            let position = 0;
            let count = 0;
            while (position < input.value.length && count < before) {
                if (/\d/.test(input.value[position])) count++;
                position++;
            }
            input.setSelectionRange(position, position);
        });
    });
})();