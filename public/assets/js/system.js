(() => {
    const initializeSystem = () => {
        const footerMessage = document.getElementById('footer-message');
        const source = document.querySelector('[data-footer-message]');
        const message = source?.dataset.footerMessage?.trim();
        const messageStatus = source?.dataset.footerStatus;
        const modalElement = document.getElementById('auth-error-modal');

        if (footerMessage) {
            footerMessage.textContent = message || 'none';
            footerMessage.style.color = messageStatus === 'success'
                ? '#6df29a'
                : (message ? '#ff8b80' : '');
            footerMessage.setAttribute('aria-live', 'polite');

            if (messageStatus === 'error' && modalElement) {
                footerMessage.style.cursor = 'pointer';
                footerMessage.style.textDecoration = 'underline';
                footerMessage.setAttribute('role', 'button');
                footerMessage.setAttribute('tabindex', '0');
                footerMessage.setAttribute('title', 'Clique para ver o retorno da API');

                const showDetails = () => window.bootstrap?.Modal
                    && window.bootstrap.Modal.getOrCreateInstance(modalElement).show();

                footerMessage.addEventListener('click', showDetails);
                footerMessage.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        showDetails();
                    }
                });
            }
        }

        const screen = document.getElementById('signin-screen');
        const login = document.getElementById('login');
        if (!screen || !login) return;

        const activate = () => screen.classList.add('is-active');
        screen.querySelector('.signin-logo')?.addEventListener('mouseenter', activate, { once: true });
        document.addEventListener('keydown', (event) => {
            if (event.ctrlKey || event.altKey || event.metaKey) return;
            activate();
            if (!['Tab', 'Shift', 'Escape', 'Enter'].includes(event.key)) login.focus();
        }, { once: true });
    };

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', initializeSystem, { once: true })
        : initializeSystem();
})();
