(() => {
    const normalize = text => text.trim().toLocaleLowerCase().replace(/\s+/g, ' ');
    [
        { prefix: 'child', selectId: 'child_id', label: 'child profiles', record: 'child' },
        { prefix: 'parent', selectId: 'prospective_parent_id', label: 'prospective parents', record: 'parent' },
    ].forEach(({ prefix, selectId, label, record }) => {
        const control = document.getElementById(prefix + 'SearchControl');
        const input = document.getElementById(prefix + 'Search');
        const select = document.getElementById(selectId);
        const status = document.getElementById(prefix + 'SearchStatus');
        if (!control || !input || !select || !status) return;

        const options = Array.from(select.options).map(option => option.cloneNode(true));
        function filterOptions() {
            const selected = select.value;
            const query = normalize(input.value);
            const matches = options.filter(option => option.value && normalize(option.textContent).includes(query));
            // Searching must not silently change a case's existing assignment.
            const visible = options.filter(option => !option.value || option.value === selected || matches.includes(option));
            select.replaceChildren(...visible.map(option => option.cloneNode(true)));
            select.value = selected;
            const keptSelection = selected && !matches.some(option => option.value === selected);
            status.textContent = matches.length + ' matching ' + label + '.'
                + (keptSelection ? ' Your current selection is kept until you choose another ' + record + '.' : '')
                + (matches.length === 0 ? ' Clear or change the search to see more profiles.' : ' Choose a ' + record + ' from the list below.');
        }
        input.addEventListener('input', filterOptions);
        control.hidden = false;
        filterOptions();
    });
})();
