(() => {
    function initialize() {
        const root = document.querySelector('[data-demo-page]');
        if (!root || root.dataset.ready) return;
        root.dataset.ready = 'true';
        let toastTimer;
        function notify(message) {
            document.querySelector('.tms-toast')?.remove();
            const toast = document.createElement('div');
            toast.className = 'tms-toast';
            toast.setAttribute('role', 'status');
            toast.textContent = message;
            document.body.append(toast);
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.remove(), 5000);
        }
        const cards = [...root.querySelectorAll('.course-card')];
        const tabs = [...root.querySelectorAll('.tab-pill')];
        let selectedStatus = '';
        const search = root.querySelector('.hero-search input');
        function filterCourses() {
            const query = (search?.value || '').trim().toLocaleLowerCase('vi');
            for (const card of cards) {
                card.hidden = !card.textContent.toLocaleLowerCase('vi').includes(query) ||
                    (selectedStatus !== '' && !card.querySelector('.status-pin').textContent.includes(selectedStatus));
            }
            let empty = root.querySelector('.tms-empty');
            if (!empty && cards.length) {
                empty = document.createElement('p');
                empty.className = 'tms-empty';
                empty.textContent = 'Không tìm thấy khóa học phù hợp.';
                root.querySelector('.courses-grid').after(empty);
            }
            if (empty) empty.hidden = cards.some(card => !card.hidden);
        }
        const statuses = ['', 'Đang ghi danh', 'Sắp khai giảng', 'Đang triển khai'];
        tabs.forEach((tab, index) => {
            const status = statuses[index] || '';
            const count = cards.filter(card => !status || card.querySelector('.status-pin').textContent.includes(status)).length;
            tab.textContent = `${status || 'Tất cả'} (${count})`;
            tab.addEventListener('click', () => {
                selectedStatus = status;
                tabs.forEach(button => button.classList.toggle('active', button === tab));
                filterCourses();
            });
        });
        search?.addEventListener('input', filterCourses);
        root.querySelector('.hero-search button')?.addEventListener('click', filterCourses);
        if (search) {
            search.value = new URLSearchParams(location.search).get('q') || '';
            filterCourses();
        }
        for (const input of root.querySelectorAll('input[placeholder^="Tìm"]')) {
            if (input === search) continue;
            input.addEventListener('input', () => {
                const query = input.value.toLocaleLowerCase('vi');
                root.querySelectorAll('tbody tr').forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase('vi').includes(query); });
            });
        }
        const certificateInput = root.querySelector('.search-bar input');
        if (certificateInput) {
            const sampleCode = certificateInput.value;
            const status = root.querySelector('.status-bar');
            root.querySelector('.search-bar button').addEventListener('click', () => {
                const valid = certificateInput.value.trim().toUpperCase() === sampleCode.toUpperCase();
                status.textContent = valid ? 'Chứng chỉ mẫu hợp lệ — Dữ liệu minh họa' : 'Không tìm thấy chứng chỉ trong dữ liệu mẫu.';
                status.style.background = valid ? '#EDFDF2' : '#FFF7ED';
                status.style.color = valid ? '#166534' : '#9A3412';
                root.querySelector('.meta-grid').hidden = !valid;
            });
        }
        root.addEventListener('click', event => {
            const disabled = event.target.closest('a.disabled');
            if (disabled) {
                event.preventDefault();
                notify('Khóa học mẫu này chưa mở ghi danh.');
                return;
            }
            const button = event.target.closest('button');
            if (!button || button.closest('.hero-search, .filter-tabs, .search-bar')) return;
            event.preventDefault();
            if (button.textContent.includes('PDF')) {
                window.print();
                return;
            }
            const row = button.closest('tr');
            if (row && button.textContent.includes('Duyệt')) {
                row.querySelectorAll('.pill-wait').forEach(pill => { pill.textContent = 'Đã duyệt (mẫu)'; pill.className = 'pill pill-ok'; });
            }
            notify('Thao tác trên dữ liệu mẫu; chưa gửi email hoặc đồng bộ sang LMS.');
        });
        root.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
            event.preventDefault();
            notify('Biểu mẫu minh họa; dữ liệu chưa được lưu vào hệ thống.');
        }));
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once: true});
    else initialize();
    if (window.Liferay?.on) Liferay.on('endNavigate', initialize);
})();
