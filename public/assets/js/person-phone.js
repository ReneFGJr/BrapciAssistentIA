(() => {
    function formatPhone(value) {
        let digits = value.replace(/\D/g, '');
        const hasCountry = /^\s*\+55/.test(value) || (digits.startsWith('55') && digits.length > 11);
        if (hasCountry) digits = digits.slice(2);
        digits = digits.slice(0, 11);
        if (!digits) return '';
        const area = digits.slice(0, 2);
        const number = digits.slice(2);
        if (!number) return '+55 (' + area;
        const split = number.length > 8 ? 5 : 4;
        return '+55 (' + area + ') ' + number.slice(0, split)
            + (number.length > split ? '.' + number.slice(split) : '');
    }

    document.querySelectorAll('[data-phone-mask]').forEach((input) => {
        input.value = formatPhone(input.value);
        input.addEventListener('input', () => {
            const value = input.value;
            const cursor = input.selectionStart ?? value.length;
            const digits = value.replace(/\D/g, '');
            const hasCountry = /^\s*\+55/.test(value) || (digits.startsWith('55') && digits.length > 11);
            const before = value.slice(0, cursor).replace(/\D/g, '').length + (hasCountry ? 0 : 2);
            input.value = formatPhone(value);
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