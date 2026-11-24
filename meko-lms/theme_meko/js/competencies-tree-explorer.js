(() => {
    const dataElement = document.getElementById('competencyTreeData');
    const tree = document.getElementById('competencyTree');
    const details = document.getElementById('competencyDetails');
    const search = document.getElementById('competencyTreeSearch');
    if (!dataElement || !tree || !details || !search) {
        return;
    }

    let frameworks;
    try {
        frameworks = JSON.parse(dataElement.textContent || '{}');
    } catch (error) {
        console.error('Unable to parse competency framework data.', error);
        details.textContent = 'Không thể đọc dữ liệu khung năng lực.';
        return;
    }

    const levelLabels = {
        basic: 'Basic',
        intermediate: 'Intermediate',
        advanced: 'Advanced',
    };
    const levelOrder = Object.keys(levelLabels);
    const expanded = new Set();
    const url = new URL(window.location.href);
    const requestedFramework = url.searchParams.get('framework');
    const initialFramework = dataElement.dataset.frameworkKey
        || Object.keys(frameworks).find(key => frameworks[key].idnumber === requestedFramework)
        || (frameworks[requestedFramework] ? requestedFramework : Object.keys(frameworks)[0]);
    const state = {
        framework: frameworks[initialFramework] ? initialFramework : Object.keys(frameworks)[0],
        domainId: null,
        competencyId: null,
        levelKey: 'basic',
    };

    const normalize = value => String(value || '').toLocaleLowerCase();
    const make = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (text !== undefined) {
            element.textContent = text;
        }
        return element;
    };
    const framework = () => frameworks[state.framework];
    const findDomain = id => framework().domains.find(domain => domain.id === id);
    const findCompetency = id => {
        for (const domain of framework().domains) {
            const competency = domain.competencies.find(item => item.id === id);
            if (competency) {
                return {domain, competency};
            }
        }
        return null;
    };
    const keyFor = (type, id) => `${state.framework}:${type}:${id}`;

    function renderFrameworkButtons() {
        document.querySelectorAll('[data-framework-choice]').forEach(button => {
            const key = button.dataset.frameworkChoice;
            const current = frameworks[key];
            const label = key === 'faculty' ? 'Faculty' : 'Students';
            button.textContent = `${label} (${current ? current.competencyCount : 0})`;
            button.setAttribute('aria-pressed', String(key === state.framework));
        });
    }

    function makeTreeButton(type, id, label, level, selected, expandable, isOpen, levelKey, count) {
        const button = make('button', `competency-tree-node competency-tree-node-${type}`);
        button.type = 'button';
        button.dataset.nodeType = type;
        button.dataset.nodeId = id;
        button.setAttribute('role', 'treeitem');
        button.setAttribute('aria-level', String(level));
        button.setAttribute('aria-current', selected ? 'true' : 'false');
        if (expandable) {
            button.setAttribute('aria-expanded', String(isOpen));
        }
        if (levelKey) {
            button.dataset.levelKey = levelKey;
            button.classList.add(`level-${levelKey}`);
            const dot = make('span', 'competency-tree-level-dot');
            dot.setAttribute('aria-hidden', 'true');
            button.append(dot);
        } else {
            const caret = make('span', 'competency-tree-caret', expandable ? '›' : '');
            caret.setAttribute('aria-hidden', 'true');
            button.append(caret);
        }
        button.append(make('span', 'competency-tree-node-label', label));
        if (type === 'domain' || type === 'level') {
            button.append(make(
                'span',
                'competency-tree-count',
                type === 'domain' ? String(count || 0) : `${count || 0} courses`
            ));
        }
        return button;
    }

    function updateTreeToggleButton() {
        const toggle = document.querySelector('[data-tree-action="toggle"]');
        if (!toggle) {
            return;
        }
        const allExpanded = framework().domains.length > 0 && framework().domains.every(domain =>
            expanded.has(keyFor('domain', domain.id))
            && domain.competencies.every(competency => expanded.has(keyFor('competency', competency.id)))
        );
        const label = allExpanded ? 'Thu gọn tất cả' : 'Mở rộng tất cả';
        toggle.textContent = allExpanded ? '−' : '+';
        toggle.setAttribute('aria-label', label);
        toggle.title = label;
    }

    function textMatches(query, values) {
        return !query || normalize(values.join(' ')).includes(query);
    }

    function renderTree() {
        const currentFramework = framework();
        tree.replaceChildren();
        if (!currentFramework || !currentFramework.domains.length) {
            tree.append(make('p', 'competency-tree-empty', 'Chưa có dữ liệu khung năng lực. Hãy kiểm tra plugin ACCEES Competencies.'));
            return;
        }

        const query = normalize(search.value.trim());
        const root = make('ul', 'competency-tree-list');
        root.setAttribute('role', 'group');

        currentFramework.domains.forEach(domain => {
            const matchingCompetencies = domain.competencies.filter(competency => {
                const levelText = Object.values(competency.levels)
                    .map(level => `${levelLabels[level.key] || ''} ${level.requirement} ${level.examples}`)
                    .join(' ');
                return textMatches(query, [
                    domain.id,
                    domain.title,
                    domain.description,
                    competency.id,
                    competency.title,
                    competency.description,
                    levelText,
                ]);
            });
            const domainMatches = textMatches(query, [domain.id, domain.title, domain.description]);
            if (query && !domainMatches && matchingCompetencies.length === 0) {
                return;
            }

            const domainOpen = query ? true : expanded.has(keyFor('domain', domain.id));
            const domainItem = make('li', 'competency-tree-item');
            domainItem.setAttribute('role', 'none');
            const domainButton = makeTreeButton(
                'domain',
                domain.id,
                domain.title,
                1,
                state.domainId === domain.id && !state.competencyId,
                true,
                domainOpen,
                null,
                domain.competencies.length
            );
            domainItem.append(domainButton);

            if (domainOpen) {
                const competencyList = make('ul', 'competency-tree-list competency-tree-children');
                competencyList.setAttribute('role', 'group');
                (query && !domainMatches ? matchingCompetencies : domain.competencies).forEach(competency => {
                    const competencyOpen = query ? true : expanded.has(keyFor('competency', competency.id));
                    const competencyItem = make('li', 'competency-tree-item');
                    competencyItem.setAttribute('role', 'none');
                    const competencyButton = makeTreeButton(
                        'competency',
                        competency.id,
                        competency.title,
                        2,
                        state.competencyId === competency.id,
                        true,
                        competencyOpen
                    );
                    competencyItem.append(competencyButton);

                    if (competencyOpen) {
                        const levelList = make('ul', 'competency-tree-list competency-tree-children');
                        levelList.setAttribute('role', 'group');
                        levelOrder.forEach(levelKey => {
                            const level = competency.levels[levelKey];
                            if (!level) {
                                return;
                            }
                            const levelItem = make('li', 'competency-tree-item');
                            levelItem.setAttribute('role', 'none');
                            levelItem.append(makeTreeButton(
                                'level',
                                competency.id,
                                levelLabels[levelKey],
                                3,
                                state.competencyId === competency.id && state.levelKey === levelKey,
                                false,
                                false,
                                levelKey,
                                level.coursesCount
                            ));
                            levelList.append(levelItem);
                        });
                        competencyItem.append(levelList);
                    }
                    competencyList.append(competencyItem);
                });
                domainItem.append(competencyList);
            }
            root.append(domainItem);
        });
        tree.append(root);
        updateTreeToggleButton();
    }

    function renderDetails() {
        const currentFramework = framework();
        details.replaceChildren();
        if (!currentFramework || !currentFramework.domains.length) {
            details.append(make('p', 'competency-tree-empty', 'Không có khung năng lực để hiển thị.'));
            return;
        }

        const domain = findDomain(state.domainId) || currentFramework.domains[0];
        const selection = state.competencyId ? findCompetency(state.competencyId) : null;
        const heading = make('div', 'competency-tree-detail-heading');
        const breadcrumb = make('div', 'competency-tree-breadcrumb');
        breadcrumb.textContent = selection
            ? `${currentFramework.title} › ${domain.title} › ${selection.competency.title}`
            : `${currentFramework.title} › ${domain.title}`;
        heading.append(breadcrumb);
        heading.append(make('h2', '', selection ? selection.competency.title : domain.title));
        const description = selection ? selection.competency.description : domain.description;
        if (description) {
            heading.append(make('p', 'competency-tree-description', description));
        }
        details.append(heading);

        if (!selection) {
            const cards = make('div', 'competency-tree-card-grid');
            domain.competencies.forEach(competency => {
                const levels = levelOrder
                    .map(levelKey => competency.levels[levelKey])
                    .filter(Boolean);
                const coursesCount = levels.reduce((total, level) => total + (level.coursesCount || 0), 0);
                const card = make('button', 'competency-tree-card');
                card.type = 'button';
                card.dataset.competencyCard = competency.id;
                card.append(make('span', 'competency-tree-card-title', competency.title));
                card.append(make(
                    'span',
                    'competency-tree-card-meta',
                    `${levels.length} target levels · ${coursesCount} courses`
                ));
                cards.append(card);
            });
            if (domain.competencies.length) {
                details.append(cards);
            } else {
                details.append(make('p', 'competency-tree-empty', 'Miền này chưa có năng lực.'));
            }
            return;
        }

        const tabs = make('div', 'competency-tree-tabs');
        tabs.setAttribute('role', 'tablist');
        tabs.setAttribute('aria-label', 'Bậc năng lực');
        levelOrder.forEach(levelKey => {
            const level = selection.competency.levels[levelKey];
            if (!level) {
                return;
            }
            const tab = make(
                'button',
                `competency-tree-tab level-${levelKey}`,
                `${levelLabels[levelKey]} (${level.coursesCount || 0})`
            );
            tab.type = 'button';
            tab.dataset.levelKey = levelKey;
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-selected', String(state.levelKey === levelKey));
            tab.id = `competency-tree-tab-${levelKey}`;
            tabs.append(tab);
        });
        details.append(tabs);

        const level = selection.competency.levels[state.levelKey]
            || selection.competency.levels.basic
            || selection.competency.levels[levelOrder.find(key => selection.competency.levels[key])];
        if (!level) {
            details.append(make('p', 'competency-tree-empty', 'Năng lực này chưa có dữ liệu bậc mục tiêu.'));
            return;
        }
        const panel = make('section', `competency-tree-level-panel level-${level.key}`);
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', `competency-tree-tab-${level.key}`);
        const requirement = make('section', 'competency-tree-content-block');
        requirement.append(make('h3', '', 'Competency requirement'));
        requirement.append(make('p', '', level.requirement || 'Chưa có mô tả yêu cầu cho bậc này.'));
        const examples = make('section', 'competency-tree-content-block');
        examples.append(make('h3', '', 'Practical examples'));
        const exampleItems = level.examples.split(/\r?\n/).map(item => item.trim()).filter(Boolean);
        if (exampleItems.length) {
            const list = make('ul', 'competency-tree-example-list');
            exampleItems.forEach(item => list.append(make('li', '', item)));
            examples.append(list);
        } else {
            examples.append(make('p', '', 'Chưa có ví dụ thực hành cho bậc này.'));
        }
        panel.append(requirement, examples);

        const footer = make('div', 'competency-tree-detail-footer');
        const cta = make('a', 'competency-tree-cta', 'Xem khóa học đạt chuẩn');
        cta.href = level.catalogUrl;
        footer.append(cta);
        panel.append(footer);
        details.append(panel);
    }

    function render() {
        renderFrameworkButtons();
        renderTree();
        renderDetails();
    }

    function selectFramework(key) {
        if (!frameworks[key]) {
            return;
        }
        state.framework = key;
        search.value = '';
        const firstDomain = framework().domains[0];
        state.domainId = firstDomain ? firstDomain.id : null;
        const firstCompetency = firstDomain && firstDomain.competencies[0];
        state.competencyId = firstCompetency ? firstCompetency.id : null;
        state.levelKey = 'basic';
        expanded.clear();
        if (firstDomain) {
            expanded.add(keyFor('domain', firstDomain.id));
            firstDomain.competencies.slice(0, 2).forEach(competency => {
                expanded.add(keyFor('competency', competency.id));
            });
        }
        url.searchParams.set('framework', framework().idnumber || key);
        window.history.replaceState({}, '', url);
        render();
    }

    document.querySelectorAll('[data-framework-choice]').forEach(button => {
        button.addEventListener('click', () => selectFramework(button.dataset.frameworkChoice));
    });

    document.querySelector('[data-tree-action="expand"]')?.addEventListener('click', () => {
        framework().domains.forEach(domain => {
            expanded.add(keyFor('domain', domain.id));
            domain.competencies.forEach(competency => expanded.add(keyFor('competency', competency.id)));
        });
        renderTree();
    });
    document.querySelector('[data-tree-action="collapse"]')?.addEventListener('click', () => {
        expanded.clear();
        renderTree();
    });
    document.querySelector('[data-tree-action="toggle"]')?.addEventListener('click', () => {
        const allExpanded = framework().domains.length > 0 && framework().domains.every(domain =>
            expanded.has(keyFor('domain', domain.id))
            && domain.competencies.every(competency => expanded.has(keyFor('competency', competency.id)))
        );
        if (allExpanded) {
            expanded.clear();
        } else {
            framework().domains.forEach(domain => {
                expanded.add(keyFor('domain', domain.id));
                domain.competencies.forEach(competency => expanded.add(keyFor('competency', competency.id)));
            });
        }
        renderTree();
    });

    search.addEventListener('input', renderTree);
    tree.addEventListener('click', event => {
        const button = event.target.closest('[data-node-type]');
        if (!button) {
            return;
        }
        const {nodeType, nodeId, levelKey} = button.dataset;
        if (nodeType === 'domain') {
            state.domainId = nodeId;
            state.competencyId = null;
            const key = keyFor('domain', nodeId);
            expanded.has(key) ? expanded.delete(key) : expanded.add(key);
        } else if (nodeType === 'competency') {
            const selection = findCompetency(nodeId);
            if (!selection) {
                return;
            }
            state.domainId = selection.domain.id;
            state.competencyId = nodeId;
            state.levelKey = 'basic';
            const key = keyFor('competency', nodeId);
            expanded.has(key) ? expanded.delete(key) : expanded.add(key);
            expanded.add(keyFor('domain', selection.domain.id));
        } else {
            const selection = findCompetency(nodeId);
            if (!selection || !selection.competency.levels[levelKey]) {
                return;
            }
            state.domainId = selection.domain.id;
            state.competencyId = nodeId;
            state.levelKey = levelKey;
            expanded.add(keyFor('domain', selection.domain.id));
            expanded.add(keyFor('competency', nodeId));
        }
        render();
    });

    details.addEventListener('click', event => {
        const card = event.target.closest('[data-competency-card]');
        if (card) {
            const selection = findCompetency(card.dataset.competencyCard);
            if (!selection) {
                return;
            }
            state.domainId = selection.domain.id;
            state.competencyId = selection.competency.id;
            state.levelKey = selection.competency.levels.basic
                ? 'basic'
                : levelOrder.find(key => selection.competency.levels[key]);
            expanded.add(keyFor('domain', selection.domain.id));
            expanded.add(keyFor('competency', selection.competency.id));
            render();
            return;
        }
        const tab = event.target.closest('[data-level-key]');
        if (!tab || !state.competencyId) {
            return;
        }
        const selection = findCompetency(state.competencyId);
        if (!selection || !selection.competency.levels[tab.dataset.levelKey]) {
            return;
        }
        state.levelKey = tab.dataset.levelKey;
        render();
    });

    const firstDomain = framework().domains[0];
    const firstCompetency = firstDomain && firstDomain.competencies[0];
    state.domainId = firstDomain ? firstDomain.id : null;
    state.competencyId = firstCompetency ? firstCompetency.id : null;
    if (firstDomain) {
        expanded.add(keyFor('domain', firstDomain.id));
        firstDomain.competencies.slice(0, 2).forEach(competency => {
            expanded.add(keyFor('competency', competency.id));
        });
    }
    render();
})();
