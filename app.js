const state = { cart: JSON.parse(localStorage.getItem('mariork-cart') || '[]') };
const drawer = document.querySelector('#cart-drawer');
const overlay = document.querySelector('#drawer-overlay');
const cartItems = document.querySelector('#cart-items');
const cartTotal = document.querySelector('#cart-total');
const bagCount = document.querySelector('#bag-count');
const quickViewModal = document.querySelector('#quick-view-modal');
const wishlist = JSON.parse(localStorage.getItem('mariork-wishlist') || '[]');
let quickViewProduct = null;
let quickViewMedia = [];
let quickViewIndex = 0;

function money(value, currency = 'USD') { return `${currency === 'GHS' ? 'GH₵' : '$'}${Number(value).toFixed(2)}`; }

function openCart() {
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('drawer-open');
}

function closeCart() {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('drawer-open');
}

function renderCart() {
    const totalItems = state.cart.reduce((sum, item) => sum + item.quantity, 0);
    const total = state.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    bagCount.textContent = totalItems;
    cartTotal.textContent = money(total, state.cart[0]?.currency || 'USD');
    localStorage.setItem('mariork-cart', JSON.stringify(state.cart));

    if (!state.cart.length) {
        cartItems.innerHTML = '<p class="cart-empty">Your bag is waiting for something lovely.</p>';
        return;
    }

    cartItems.innerHTML = state.cart.map((item, index) => `<div class="cart-line"><div><p>${item.name}</p><span>${money(item.price, item.currency)} · Qty ${item.quantity}</span></div><button type="button" data-remove="${index}" aria-label="Remove ${item.name}">×</button></div>`).join('');
}

document.querySelectorAll('.quick-add').forEach((button) => {
    button.addEventListener('click', () => {
        const existing = state.cart.find((item) => item.name === button.dataset.product);
        if (existing) existing.quantity += 1;
        else state.cart.push({ name: button.dataset.product, price: Number(button.dataset.price), currency: button.dataset.currency || 'USD', quantity: 1 });
        renderCart();
        openCart();
    });
});

document.querySelectorAll('.product-image[data-media]').forEach((image) => {
    let gallery;
    try {
        gallery = JSON.parse(image.dataset.media);
    } catch (error) {
        gallery = [];
    }
    if (!Array.isArray(gallery) || gallery.length < 2) return;
    const video = image.parentElement.querySelector('.product-video');
    let imageIndex = 0;
    window.setInterval(() => {
        image.classList.add('is-changing');
        if (video) video.classList.add('is-changing');
        window.setTimeout(() => {
            imageIndex = (imageIndex + 1) % gallery.length;
            const media = gallery[imageIndex];
            if (media.type === 'video' && video) {
                image.hidden = true;
                video.hidden = false;
                video.src = media.src;
                video.play().catch(() => {});
            } else {
                if (video) {
                    video.pause();
                    video.hidden = true;
                }
                image.hidden = false;
                image.src = media.src;
            }
            image.classList.remove('is-changing');
            if (video) video.classList.remove('is-changing');
        }, 220);
    }, 5000);
});

document.querySelectorAll('.rotating-banner[data-media]').forEach((banner) => {
    let media;
    try { media = JSON.parse(banner.dataset.media); } catch (error) { media = []; }
    if (!Array.isArray(media) || media.length < 2) return;
    const image = banner.querySelector('img');
    const video = banner.querySelector('.banner-video');
    let mediaIndex = 0;
    window.setInterval(() => {
        banner.classList.add('is-changing');
        window.setTimeout(() => {
            mediaIndex = (mediaIndex + 1) % media.length;
            const current = media[mediaIndex];
            if (current.type === 'video' && video) {
                image.hidden = true;
                video.hidden = false;
                video.src = current.src;
                video.muted = true;
                video.play().catch(() => {});
            } else {
                if (video) { video.pause(); video.hidden = true; }
                image.hidden = false;
                image.src = current.src;
            }
            banner.classList.remove('is-changing');
        }, 260);
    }, 5000);
});

document.querySelectorAll('.wishlist-button').forEach((button) => {
    button.addEventListener('click', async () => {
        const productName = button.dataset.wishlist;
        const shouldSave = !button.classList.contains('is-saved');
        button.classList.toggle('is-saved', shouldSave);
        if (window.MARIORK_CUSTOMER_LOGGED_IN) {
            try {
                const response = await fetch('customers/wishlist.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ product_id: button.dataset.productId, saved: String(shouldSave) }) });
                const result = await response.json();
                if (!result.ok) throw new Error('wishlist');
                document.querySelector('#wishlist-count').textContent = result.count;
                showRitualToast(shouldSave ? 'Added to your rituals' : 'Removed from favorites');
            } catch (error) {
                button.classList.toggle('is-saved', !shouldSave);
                showRitualToast('We could not update your rituals');
            }
            return;
        }
        const index = wishlist.indexOf(productName);
        if (shouldSave && index === -1) wishlist.push(productName);
        if (!shouldSave && index !== -1) wishlist.splice(index, 1);
        localStorage.setItem('mariork-wishlist', JSON.stringify(wishlist));
        document.querySelector('#wishlist-count').textContent = wishlist.length;
        showRitualToast(shouldSave ? 'Added to your rituals' : 'Removed from favorites');
    });
});

function showRitualToast(message) {
    const toast = document.querySelector('#ritual-toast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(window.ritualToastTimer);
    window.ritualToastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2400);
}

document.querySelectorAll('.quick-view-trigger').forEach((button) => {
    button.addEventListener('click', () => {
        quickViewProduct = button.dataset;
        try { quickViewMedia = JSON.parse(quickViewProduct.media); } catch (error) { quickViewMedia = []; }
        quickViewIndex = 0;
        document.querySelector('#quick-view-category').textContent = quickViewProduct.category;
        document.querySelector('#quick-view-name').textContent = quickViewProduct.name;
        document.querySelector('#quick-view-price').textContent = money(quickViewProduct.price, quickViewProduct.currency);
        document.querySelector('#quick-view-description').textContent = quickViewProduct.description;
        renderQuickViewMedia();
        quickViewModal.classList.add('is-open');
        quickViewModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('drawer-open');
    });
});

function renderQuickViewMedia() {
    const image = document.querySelector('#quick-view-image');
    const video = document.querySelector('#quick-view-video');
    const current = quickViewMedia[quickViewIndex];
    if (!current) return;
    if (current.type === 'video') {
        image.hidden = true;
        video.hidden = false;
        video.src = current.src;
        video.play().catch(() => {});
    } else {
        video.pause();
        video.removeAttribute('src');
        video.load();
        video.hidden = true;
        image.hidden = false;
        image.src = current.src;
        image.alt = quickViewProduct.name;
    }
    document.querySelector('#quick-view-counter').textContent = `${quickViewIndex + 1} / ${quickViewMedia.length}`;
    const hasMultiple = quickViewMedia.length > 1;
    document.querySelector('.quick-view-prev').hidden = !hasMultiple;
    document.querySelector('.quick-view-next').hidden = !hasMultiple;
}

function moveQuickView(direction) {
    if (!quickViewMedia.length) return;
    quickViewIndex = (quickViewIndex + direction + quickViewMedia.length) % quickViewMedia.length;
    renderQuickViewMedia();
}

document.querySelector('.quick-view-prev').addEventListener('click', () => moveQuickView(-1));
document.querySelector('.quick-view-next').addEventListener('click', () => moveQuickView(1));

function closeQuickView() {
    quickViewModal.classList.remove('is-open');
    quickViewModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('drawer-open');
}

document.querySelector('.quick-view-close').addEventListener('click', closeQuickView);
quickViewModal.addEventListener('click', (event) => {
    if (event.target === quickViewModal) closeQuickView();
});
document.querySelector('#quick-view-add').addEventListener('click', () => {
    if (!quickViewProduct) return;
    const existing = state.cart.find((item) => item.name === quickViewProduct.name);
    if (existing) existing.quantity += 1;
    else state.cart.push({ name: quickViewProduct.name, price: Number(quickViewProduct.price), currency: quickViewProduct.currency || 'USD', quantity: 1 });
    renderCart();
    closeQuickView();
    openCart();
});

document.querySelector('.checkout-button').addEventListener('click', () => {
    if (!state.cart.length) return;
    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'customers/checkout.php';
    const items = document.createElement('input');
    items.type = 'hidden';
    items.name = 'items';
    items.value = JSON.stringify(state.cart);
    form.appendChild(items);
    document.body.appendChild(form);
    form.submit();
});

const announcements = [
    ['Complimentary delivery on orders over $150', 'curated for your next chapter'],
    ['New season, new signature', 'discover the latest arrivals'],
    ['Your ritual, beautifully considered', 'save your favourites to My Rituals'],
];
let announcementIndex = 0;
setInterval(() => {
    announcementIndex = (announcementIndex + 1) % announcements.length;
    document.querySelectorAll('.announcement-message').forEach((message, index) => {
        message.classList.add('is-changing');
        window.setTimeout(() => {
            message.textContent = announcements[announcementIndex][index];
            message.classList.remove('is-changing');
        }, 180);
    });
}, 5000);

document.querySelector('.bag-button').addEventListener('click', openCart);
document.querySelector('.close-cart').addEventListener('click', closeCart);
overlay.addEventListener('click', closeCart);
cartItems.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-remove]');
    if (removeButton) { state.cart.splice(Number(removeButton.dataset.remove), 1); renderCart(); }
});

document.querySelectorAll('.filter-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.filter-tab').forEach((item) => item.classList.remove('active'));
        tab.classList.add('active');
        filterProducts(tab.dataset.filter, document.querySelector('#product-search').value);
    });
});

document.querySelector('#product-search').addEventListener('input', (event) => {
    const activeFilter = document.querySelector('.filter-tab.active').dataset.filter;
    filterProducts(activeFilter, event.target.value);
});

document.querySelectorAll('[data-bundle-filter]').forEach((link) => {
    link.addEventListener('click', () => {
        document.querySelector('#shop').scrollIntoView({ behavior: 'smooth' });
        document.querySelector('[data-filter="Bundles"]').click();
    });
});

function filterProducts(category, query) {
    let visible = 0;
    document.querySelectorAll('.product-card').forEach((card) => {
        const matchesCategory = category === 'All' || card.dataset.category === category;
        const matchesQuery = card.dataset.name.includes(query.trim().toLowerCase());
        card.hidden = !(matchesCategory && matchesQuery);
        if (!card.hidden) visible += 1;
    });
    document.querySelector('#empty-state').hidden = visible !== 0;
}

document.querySelector('.newsletter').addEventListener('submit', (event) => {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button');
    button.textContent = '✓';
    event.currentTarget.querySelector('input').value = '';
});

document.querySelector('.search-toggle').addEventListener('click', () => {
    document.querySelector('#product-search').focus();
    document.querySelector('#shop').scrollIntoView({ behavior: 'smooth' });
});

renderCart();
