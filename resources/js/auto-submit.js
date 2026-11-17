// Filter forms apply a dropdown choice as soon as it changes.
document.querySelectorAll('form[data-auto-submit]').forEach((form) => {
    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => form.requestSubmit());
    });
});
