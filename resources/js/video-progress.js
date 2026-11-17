const panel = document.querySelector('[data-video-progress]');

if (panel) {
    const percent = panel.querySelector('[data-progress-percent]');
    const bar = panel.querySelector('[data-progress-bar]');
    const track = panel.querySelector('[data-progress-track]');
    const message = panel.querySelector('[data-progress-message]');

    const poll = async () => {
        try {
            const response = await fetch(panel.dataset.statusUrl, { headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (data.finished) {
                window.location.reload();
                return;
            }

            percent.textContent = `${data.progress}%`;
            bar.style.width = `${data.progress}%`;
            track.setAttribute('aria-valuenow', data.progress);
            message.textContent = data.message ?? '';
        } catch {
            // Keep trying; the next poll may succeed.
        }

        setTimeout(poll, 2000);
    };

    setTimeout(poll, 2000);
}
