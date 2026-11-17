// Buttons with data-copy-target copy the text of the field they point to.
document.querySelectorAll('[data-copy-target]').forEach((button) => {
    const original = button.textContent;

    button.addEventListener('click', async () => {
        const field = document.querySelector(button.dataset.copyTarget);

        if (!field) {
            return;
        }

        try {
            await navigator.clipboard.writeText(field.value ?? field.textContent);
            button.textContent = 'Copied';
        } catch {
            field.select?.();
            button.textContent = 'Press Ctrl+C';
        }

        setTimeout(() => {
            button.textContent = original;
        }, 1500);
    });
});
