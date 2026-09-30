document.querySelectorAll('[data-notepad-person]').forEach(select => {
    const options = Array.from(select.options).filter(option => option.value);
    const input = document.createElement('input');
    input.id = select.id + '-search';
    input.autocomplete = 'off';
    input.placeholder = 'Digite o nome e selecione uma Person';
    const list = document.createElement('datalist');
    list.id = select.id + '-options';
    input.setAttribute('list', list.id);
    options.forEach(option => {
        const item = document.createElement('option');
        item.value = option.textContent;
        list.append(item);
    });
    input.value = options.find(option => option.value === select.value)?.textContent || '';
    select.before(input, list);
    document.querySelector('label[for="' + select.id + '"]').htmlFor = input.id;
    select.hidden = true;
    const sync = () => {
        const match = options.find(option => option.textContent === input.value);
        select.value = match ? match.value : '';
        input.setCustomValidity(!input.value || match ? '' : 'Selecione uma Person da lista.');
    };
    input.addEventListener('input', sync);
    input.addEventListener('change', sync);
});
