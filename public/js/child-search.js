(() => {
    const control = document.getElementById('childSearchControl');
    const input = document.getElementById('childSearch');
    const select = document.getElementById('child_id');
    const status = document.getElementById('childSearchStatus');
    if (!control || !input || !select || !status) return;

    const options = Array.from(select.options).map(option => option.cloneNode(true));
    const normalize = text => text.trim().toLocaleLowerCase().replace(/\s+/g, ' ');
    function filterOptions() {
        const selected = select.value;
        const query = normalize(input.value);
        const matches = options.filter(option => option.value && normalize(option.textContent).includes(query));
        // Preserve an existing selection while searching, including on edit/validation return.
        const visible = options.filter(option => !option.value || option.value === selected || matches.includes(option));
        select.replaceChildren(...visible.map(option => option.cloneNode(true)));
        select.value = selected;
        const keptSelection = selected && !matches.some(option => option.value === selected);
        status.textContent = matches.length + ' matching child profiles.'
            + (keptSelection ? ' Your current selection is kept until you choose another child.' : '')
            + (matches.length === 0 ? ' Clear or change the search to see more profiles.' : ' Choose a child from the list below.');
    }
    input.addEventListener('input', filterOptions);
    control.hidden = false;
    filterOptions();
})();
