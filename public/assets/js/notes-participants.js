(() => {
    const form = document.getElementById('participant-search-form');
    if (!form) return;
    const input = document.getElementById('participant-name');
    const id = document.getElementById('participant-id');
    const add = document.getElementById('participant-add');
    const results = document.getElementById('participant-results');
    let timer, request, version = 0;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        request?.abort();
        const current = ++version;
        id.value = '';
        add.disabled = true;
        results.replaceChildren();
        results.hidden = true;
        const query = input.value.trim();
        if (query.length < 2) return;
        timer = setTimeout(async () => {
            request = new AbortController();
            try {
                const url = new URL(form.dataset.searchUrl, location.href);
                url.searchParams.set('q', query);
                const response = await fetch(url, {signal: request.signal, headers: {Accept: 'application/json'}});
                if (!response.ok) throw new Error('Não foi possível buscar participantes.');
                const people = await response.json();
                if (!Array.isArray(people)) throw new Error('Recarregue a página para buscar participantes.');
                if (current !== version) return;
                results.replaceChildren();
                results.hidden = people.length === 0;
                if (!people.length) window.setOperationStatus('Nenhuma Person disponível para as palavras informadas.');
                people.forEach(person => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action bg-dark text-light border-secondary';
                    button.textContent = person.full_name + ' (' + person.nickname + ') — #' + person.id;
                    button.addEventListener('click', () => {
                        input.value = person.full_name;
                        id.value = person.id;
                        add.disabled = false;
                        results.hidden = true;
                        results.replaceChildren();
                        add.focus();
                    });
                    results.append(button);
                });
            } catch (error) {
                if (error.name !== 'AbortError' && current === version) {
                    window.setOperationStatus(error.message, 'error');
                }
            }
        }, 250);
    });
    form.addEventListener('submit', event => {
        if (!id.value) {
            event.preventDefault();
            window.setOperationStatus('Selecione uma Person nos resultados da busca.', 'error');
        }
    });
})();
