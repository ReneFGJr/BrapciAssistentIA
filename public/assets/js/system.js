window.setOperationStatus = (message, status = 'info') => {
    const footer = document.getElementById('footer-message');
    if (!footer) return;
    footer.textContent = message || 'none';
    footer.style.color = status === 'success' ? '#6df29a' : (status === 'error' ? '#ff8b80' : '');
    footer.setAttribute('aria-live', 'polite');
};
(() => {
    const initializeSystem = () => {
        const footerMessage = document.getElementById('footer-message');
        const sources = [...document.querySelectorAll('[data-footer-message]')].filter(element => element.dataset.footerMessage?.trim());
        const source = sources.find(element => element.dataset.footerStatus === 'error') || sources[0];
        const message = source?.dataset.footerMessage?.trim();
        const messageStatus = source?.dataset.footerStatus;
        const modalElement = document.getElementById('auth-error-modal');

        if (footerMessage) {
            window.setOperationStatus(message, messageStatus);

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

        const noteTitleFilter = document.getElementById('note-title-filter');
        if (noteTitleFilter) {
            const noteItems = [...document.querySelectorAll('[data-note-item]')];
            const emptyResult = document.querySelector('[data-note-filter-empty]');

            noteTitleFilter.addEventListener('input', () => {
                const query = noteTitleFilter.value.trim().toLocaleLowerCase('pt-BR');
                let visibleItems = 0;

                noteItems.forEach((item) => {
                    const title = (item.dataset.noteTitle || '').toLocaleLowerCase('pt-BR');
                    const visible = title.includes(query);
                    item.hidden = !visible;
                    if (visible) visibleItems++;
                });

                if (emptyResult) emptyResult.hidden = visibleItems !== 0;
            });
        }

        document.querySelectorAll('[data-auto-grow]').forEach((textarea) => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        });

        document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!window.confirm(form.dataset.confirmDelete || 'Deseja excluir este registro?')) event.preventDefault();
            });
        });

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
