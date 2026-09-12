document.addEventListener('pointerover', (event) => {
    const button = event.target.closest('button, .button');
    if (button) button.classList.add('is-pointed');
});

document.addEventListener('pointerout', (event) => {
    const button = event.target.closest('button, .button');
    if (button && !button.matches(':hover')) button.classList.remove('is-pointed');
});

document.addEventListener('focusin', (event) => {
    const button = event.target.closest('button, .button');
    if (button) button.classList.add('is-focused');
});

document.addEventListener('focusout', (event) => {
    const button = event.target.closest('button, .button');
    if (button) button.classList.remove('is-focused');
});

document.addEventListener('pointerdown', (event) => {
    const button = event.target.closest('button, .button');
    if (button) button.classList.add('is-pressed');
});

document.addEventListener('pointerup', () => {
    document.querySelectorAll('.is-pressed').forEach((button) => button.classList.remove('is-pressed'));
});
