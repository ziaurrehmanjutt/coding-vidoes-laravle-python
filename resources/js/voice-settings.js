import { csrfToken } from './csrf';

const form = document.querySelector('[data-voice-form]');

if (form) {
    form.querySelectorAll('input[type="range"]').forEach((slider) => {
        const output = slider.closest('div').querySelector('[data-range-output]');
        const update = () => {
            output.textContent = `${slider.value}${output.dataset.unit}`;
        };

        slider.addEventListener('input', update);
    });

    const button = form.querySelector('[data-preview-button]');
    const status = form.querySelector('[data-preview-status]');
    let audio = null;

    button.addEventListener('click', async () => {
        const data = new FormData(form);
        data.delete('_method');

        button.disabled = true;
        status.textContent = 'Generating a sample...';

        try {
            const response = await fetch(form.dataset.previewUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: data,
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                status.textContent = error.message ?? 'The preview failed. Check the settings and try again.';
                return;
            }

            audio?.pause();
            audio = new Audio(URL.createObjectURL(await response.blob()));
            await audio.play();
            status.textContent = '';
        } catch {
            status.textContent = 'Could not reach the server.';
        } finally {
            button.disabled = false;
        }
    });
}
