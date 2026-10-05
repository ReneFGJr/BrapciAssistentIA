function clock(){document.getElementById('server-clock').textContent=new Date().toLocaleString('pt-BR');} clock(); setInterval(clock,1000);

document.querySelectorAll('form[data-import-form], form#person-import').forEach(function (form) {
    form.addEventListener('submit', function () {
        var button = form.querySelector('[data-import-submit]');
        var loading = form.querySelector('[data-import-loading]');

        if (button) {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        }

        if (loading) {
            loading.classList.add('is-visible');
            loading.setAttribute('aria-hidden', 'false');
        }
    });
});
