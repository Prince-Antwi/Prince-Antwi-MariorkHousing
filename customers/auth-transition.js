const authLinks = document.querySelectorAll('[data-auth-link]');

function navigateWithMorph(link) {
    if (document.startViewTransition) {
        document.startViewTransition(() => { window.location.href = link.href; });
        return;
    }

    document.body.classList.add('auth-leaving');
    window.setTimeout(() => { window.location.href = link.href; }, 260);
}

authLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        navigateWithMorph(link);
    });
});

if (document.body.classList.contains('auth-page')) {
    document.body.classList.add('auth-ready');
}
