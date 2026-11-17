import { csrfToken } from './csrf';

const form = document.querySelector('[data-predict-form]');

if (form) {
    const button = form.querySelector('[data-predict-button]');
    const result = form.querySelector('[data-predict-result]');
    const codeField = form.querySelector('[name="code"]');
    const languageField = form.querySelector('[name="language"]');

    const show = (message, tone = 'info', output = null) => {
        const colors = {
            info: 'text-slate-600 dark:text-slate-400',
            success: 'text-emerald-700 dark:text-emerald-300',
            error: 'text-red-600 dark:text-red-400',
        };

        result.replaceChildren();

        const line = document.createElement('p');
        line.className = colors[tone];
        line.textContent = message;
        result.append(line);

        if (output !== null) {
            const pre = document.createElement('pre');
            pre.className = 'mt-1 overflow-x-auto rounded-lg bg-slate-900 p-3 font-mono text-slate-100';
            pre.textContent = output === '' ? '(no output)' : output;
            result.append(pre);
        }
    };

    button.addEventListener('click', async () => {
        const code = codeField.value.trim();

        if (code === '') {
            show('Write some code first.', 'error');
            return;
        }

        button.disabled = true;
        show('Running the code...');

        try {
            const response = await fetch(form.dataset.predictUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ code, language: languageField.value }),
            });
            const data = await response.json();

            if (!response.ok) {
                show(data.message ?? 'Could not run the code.', 'error');
                return;
            }

            if (!data.ok) {
                show(data.supported === false ? data.error : `The code raised an error: ${data.error}`, 'error');
                return;
            }

            const optionInputs = [...form.querySelectorAll('[data-option-input]')];
            const radios = [...form.querySelectorAll('[data-correct-radio]')];
            const matchIndex = optionInputs.findIndex((input) => input.value.trim() === data.output.trim());

            if (matchIndex === -1) {
                show('The code printed this, but none of your options match it:', 'error', data.output);
                return;
            }

            radios[matchIndex].checked = true;
            show(`The code printed this. Option ${String.fromCharCode(65 + matchIndex)} matches, so it is marked correct.`, 'success', data.output);
        } catch {
            show('Could not reach the server.', 'error');
        } finally {
            button.disabled = false;
        }
    });
}
