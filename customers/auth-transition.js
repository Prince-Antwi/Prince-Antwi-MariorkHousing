const authLinks = document.querySelectorAll('[data-auth-link]');
const savedDirection = sessionStorage.getItem('mariork-auth-direction');

if (document.body.classList.contains('auth-page')) {
    document.body.classList.add('auth-ready');
    if (savedDirection) {
        document.body.classList.add(savedDirection === 'register' ? 'auth-enter-from-right' : 'auth-enter-from-left');
        sessionStorage.removeItem('mariork-auth-direction');
    }
}

function navigateWithSlide(link) {
    const movingToRegister = link.href.toLowerCase().includes('register.php');
    sessionStorage.setItem('mariork-auth-direction', movingToRegister ? 'register' : 'login');
    document.body.classList.add(movingToRegister ? 'auth-leaving-left' : 'auth-leaving-right');
    window.setTimeout(() => { window.location.href = link.href; }, 360);
}

authLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        navigateWithSlide(link);
    });
});
