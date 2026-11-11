document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-optin-multistep]');
    if (!form) return;

    const steps = [...form.querySelectorAll('[data-optin-step]')];
    const progressBar = form.querySelector('[data-optin-progress-bar]');
    const progressLabel = form.querySelector('[data-optin-step-label]');
    
    // Check if there is a server-rendered error to jump straight to that step
    let currentStep = 0;
    const errorStep = form.querySelector('.error')?.closest('[data-optin-step]');
    if (errorStep) {
        currentStep = Math.max(0, steps.indexOf(errorStep));
    }

    const validateStep = (index) => {
        const step = steps[index];
        if (!step) return true;

        if (index === 0) {
            const firstNameInput = step.querySelector('#first_name');
            if (firstNameInput && !firstNameInput.value.trim()) {
                showInlineError(step, 'Please enter your first name.');
                firstNameInput.focus();
                return false;
            }
        }

        if (index === 1) {
            const emailInput = step.querySelector('#email');
            const emailValue = emailInput ? emailInput.value.trim() : '';
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailInput || !emailValue || !emailRegex.test(emailValue)) {
                showInlineError(step, 'Please enter a valid email address.');
                if (emailInput) emailInput.focus();
                return false;
            }
        }

        clearInlineError(step);
        return true;
    };

    const showInlineError = (step, message) => {
        let errEl = step.querySelector('.inline-client-error');
        if (!errEl) {
            errEl = document.createElement('p');
            errEl.className = 'error inline-client-error';
            step.appendChild(errEl);
        }
        errEl.textContent = message;
    };

    const clearInlineError = (step) => {
        const errEl = step.querySelector('.inline-client-error');
        if (errEl) errEl.remove();
    };

    const render = (focusInput = false) => {
        steps.forEach((step, idx) => {
            const isCurrent = idx === currentStep;
            step.hidden = !isCurrent;
            step.setAttribute('aria-hidden', isCurrent ? 'false' : 'true');
            if (isCurrent) {
                step.classList.add('optin-step--active');
                if (focusInput) {
                    const input = step.querySelector('input:not([type="hidden"]):not([type="checkbox"])');
                    if (input) {
                        setTimeout(() => input.focus(), 50);
                    }
                }
            } else {
                step.classList.remove('optin-step--active');
            }
        });

        if (progressBar) {
            const percentage = ((currentStep + 1) / steps.length) * 100;
            progressBar.style.width = `${percentage}%`;
        }

        if (progressLabel) {
            progressLabel.textContent = `Step ${currentStep + 1} of ${steps.length}`;
        }
    };

    // Next button clicks
    form.querySelectorAll('[data-optin-next]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (validateStep(currentStep)) {
                currentStep = Math.min(currentStep + 1, steps.length - 1);
                render(true);
            }
        });
    });

    // Back button clicks
    form.querySelectorAll('[data-optin-back]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            clearInlineError(steps[currentStep]);
            currentStep = Math.max(currentStep - 1, 0);
            render(true);
        });
    });

    // Enter key handling on text/tel/email inputs
    form.querySelectorAll('input:not([type="checkbox"])').forEach((input) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (currentStep < steps.length - 1) {
                    if (validateStep(currentStep)) {
                        currentStep = Math.min(currentStep + 1, steps.length - 1);
                        render(true);
                    }
                } else {
                    form.requestSubmit();
                }
            }
        });
    });

    // Final submit loading state
    form.addEventListener('submit', (e) => {
        const phoneInput = form.querySelector('#phone');
        if (phoneInput && !phoneInput.value.trim()) {
            e.preventDefault();
            showInlineError(steps[2], 'Please enter your mobile phone number.');
            phoneInput.focus();
            return;
        }

        const consentCheckbox = form.querySelector('#consent');
        if (consentCheckbox && !consentCheckbox.checked) {
            e.preventDefault();
            showInlineError(steps[2], 'Please confirm your consent to receive the guide.');
            consentCheckbox.focus();
            return;
        }

        const submitBtn = form.querySelector('[data-optin-submit]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'SENDING ACCESS LINK…';
            form.setAttribute('aria-busy', 'true');
        }
    });

    render(false);
});
