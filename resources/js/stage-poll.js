// Rows that are still rendering update their label on their own, and the page reloads when a video finishes.
const rendering = document.querySelectorAll('[data-poll-url]');

if (rendering.length > 0) {
    const poll = async () => {
        let finished = false;

        await Promise.all([...rendering].map(async (flag) => {
            try {
                const response = await fetch(flag.dataset.pollUrl, { headers: { Accept: 'application/json' } });
                const data = await response.json();

                if (data.finished) {
                    finished = true;
                    return;
                }

                flag.querySelector('[data-poll-text]').textContent = `${data.label} ${data.progress}%`;
            } catch {
                // Try again on the next round.
            }
        }));

        if (finished) {
            window.location.reload();
            return;
        }

        setTimeout(poll, 3000);
    };

    setTimeout(poll, 3000);
}
