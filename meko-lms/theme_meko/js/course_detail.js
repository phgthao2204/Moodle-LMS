document.addEventListener('click', function(event) {
    const target = event.target.closest('[data-section-target]');
    if (!target) {
        return;
    }

    const tiles = target.closest('[data-meko-tiles-view]');
    if (!tiles) {
        return;
    }

    const sectionNumber = target.getAttribute('data-section-target');
    const panel = tiles.querySelector('[data-section-panel="' + sectionNumber + '"]');
    if (!panel) {
        return;
    }

    tiles.querySelectorAll('[data-section-target][aria-pressed]').forEach(function(button) {
        const selected = button.getAttribute('data-section-target') === sectionNumber;
        button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        button.classList.toggle('is-active', selected);
    });

    tiles.querySelectorAll('[data-section-panel]').forEach(function(sectionPanel) {
        const selected = sectionPanel === panel;
        sectionPanel.hidden = !selected;
        sectionPanel.classList.toggle('is-active', selected);
    });
});
