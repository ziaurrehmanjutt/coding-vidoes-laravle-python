// Ticking questions shows a bar with bulk actions; "select all" ticks the whole page.
const bar = document.querySelector('[data-bulk-bar]');

if (bar) {
    const items = [...document.querySelectorAll('[data-bulk-item]')];
    const all = document.querySelector('[data-bulk-all]');
    const count = bar.querySelector('[data-bulk-count]');

    const update = () => {
        const selected = items.filter((item) => item.checked).length;

        bar.classList.toggle('hidden', selected === 0);
        count.textContent = `${selected} selected`;

        if (all) {
            all.checked = selected > 0 && selected === items.length;
            all.indeterminate = selected > 0 && selected < items.length;
        }
    };

    items.forEach((item) => item.addEventListener('change', update));
    all?.addEventListener('change', () => {
        items.forEach((item) => {
            item.checked = all.checked;
        });
        update();
    });

    update();
}
