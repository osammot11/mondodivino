document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-progressive-form]');

    if (!form) return;

    const steps = [...form.querySelectorAll('[data-step]')];
    const errorStep = form.querySelector('.error')?.closest('[data-step]');
    let current = Math.max(0, errorStep ? steps.indexOf(errorStep) : 0);

    const render = () => {
        steps.forEach((step, index) => {
            step.hidden = index !== current;
            step.setAttribute('aria-hidden', index === current ? 'false' : 'true');
        });
        form.querySelector('[data-progress-label]').textContent = `Step ${current + 1} of ${steps.length}`;
        form.querySelector('[data-progress-bar]').style.width = `${((current + 1) / steps.length) * 100}%`;
    };

    form.querySelectorAll('[data-next]').forEach((button) => button.addEventListener('click', () => {
        const age = form.querySelector('input[name="age_confirmed"]:checked');

        if (current === 0 && age?.value === '0') {
            form.requestSubmit();
            return;
        }

        current = Math.min(current + 1, steps.length - 1);
        render();
        steps[current].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));

    form.querySelectorAll('[data-back]').forEach((button) => button.addEventListener('click', () => {
        current = Math.max(current - 1, 0);
        render();
        steps[current].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));

    form.querySelectorAll('[data-max-group]').forEach((input) => input.addEventListener('change', () => {
        const group = [...form.querySelectorAll(`[data-max-group="${input.dataset.maxGroup}"]`)];
        const limitReached = group.filter((item) => item.checked).length >= Number(input.dataset.max);
        group.forEach((item) => { item.disabled = limitReached && !item.checked; });
    }));

    form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-submit]');
        if (!submit) return;
        submit.disabled = true;
        submit.querySelector('[data-submit-text]').textContent = 'SAVING YOUR PROFILE…';
        form.setAttribute('aria-busy', 'true');
    });

    render();

    if (errorStep) {
        requestAnimationFrame(() => requestAnimationFrame(() => {
            errorStep.scrollIntoView({ block: 'start' });
        }));
    }
});
