(function() {
    const grid = document.querySelector('[data-course-grid]');
    if (!grid) {
        return;
    }

    const cards = Array.from(grid.querySelectorAll('[data-course-card]'));
    const filters = Array.from(document.querySelectorAll('[data-course-filter]'));
    const search = document.querySelector('[data-course-search]');
    const sort = document.querySelector('[data-course-sort]');
    const noResults = document.querySelector('[data-course-no-results]');
    let activeFilter = 'all';

    function updateCourses() {
        const query = search ? search.value.trim().toLocaleLowerCase() : '';
        let visibleCount = 0;

        cards.forEach(function(card) {
            const matchesFilter = activeFilter === 'all' || card.dataset.courseStatus === activeFilter;
            const matchesSearch = !query || card.dataset.courseSearchText.toLocaleLowerCase().includes(query);
            const visible = matchesFilter && matchesSearch;
            card.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        if (noResults) {
            noResults.hidden = visibleCount > 0;
        }
    }

    filters.forEach(function(button) {
        button.addEventListener('click', function() {
            activeFilter = button.dataset.courseFilter;
            filters.forEach(function(filter) {
                const selected = filter === button;
                filter.classList.toggle('is-active', selected);
                filter.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            updateCourses();
        });
    });

    if (search) {
        search.addEventListener('input', updateCourses);
    }

    if (sort) {
        sort.addEventListener('change', function() {
            const sorters = {
                recent: function(a, b) {
                    return Number(b.dataset.courseSorttime) - Number(a.dataset.courseSorttime);
                },
                name: function(a, b) {
                    return a.dataset.courseName.localeCompare(b.dataset.courseName);
                },
                progress: function(a, b) {
                    return Number(b.dataset.courseProgress) - Number(a.dataset.courseProgress);
                },
            };
            cards.sort(sorters[sort.value] || sorters.recent).forEach(function(card) {
                grid.appendChild(card);
            });
        });
    }

    document.querySelectorAll('[data-course-view]').forEach(function(button) {
        button.addEventListener('click', function() {
            const listview = button.dataset.courseView === 'list';
            grid.classList.toggle('is-list-view', listview);
            document.querySelectorAll('[data-course-view]').forEach(function(viewbutton) {
                const selected = viewbutton === button;
                viewbutton.classList.toggle('is-active', selected);
                viewbutton.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
        });
    });
})();
