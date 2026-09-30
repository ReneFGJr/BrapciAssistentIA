(() => {
    const select = document.getElementById('notes-person');
    if (!select) return;
    const options = Array.from(select.options).filter(option => option.value);
    const input = document.createElement('input');
    input.id = 'notes-person-search';
    input.className = 'form-control mb-3';
    input.required = true;
    input.autocomplete = 'off';
    input.placeholder = 'Digite o nome e selecione uma pessoa';
    input.setAttribute('list', 'notes-person-options');
    const list = document.createElement('datalist');
    list.id = 'notes-person-options';
    options.forEach(option => {
        const suggestion = document.createElement('option');
        suggestion.value = option.textContent;
        list.append(suggestion);
    });
    input.value = options.find(option => option.value === select.value)?.textContent || '';
    select.before(input, list);
    document.querySelector('label[for="notes-person"]').htmlFor = input.id;
    select.hidden = true;
    select.required = false;
    const sync = () => {
        const match = options.find(option => option.textContent === input.value);
        select.value = match ? match.value : '';
        input.setCustomValidity(match ? '' : 'Selecione uma pessoa da lista de sugestões.');
    };
    input.addEventListener('input', sync);
    input.addEventListener('change', sync);
    sync();
})();
