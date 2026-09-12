const selected = {};
const toast = document.querySelector('#bundle-toast');

function showToast(message) {
    toast.textContent = message;
    toast.classList.add('is-visible');
    window.setTimeout(() => toast.classList.remove('is-visible'), 2600);
}

document.querySelectorAll('.choice-button').forEach((button) => {
    button.addEventListener('click', () => {
        selected[button.dataset.choiceCategory] = { name: button.dataset.choiceName, price: Number(button.dataset.choicePrice) };
        document.querySelectorAll(`[data-choice-category="${button.dataset.choiceCategory}"]`).forEach((item) => item.classList.remove('is-selected'));
        button.classList.add('is-selected');
        updateBuilder();
    });
});

function updateBuilder() {
    const selectedItems = Object.values(selected);
    const total = selectedItems.reduce((sum, item) => sum + item.price, 0);
    const price = document.querySelector('#builder-price');
    const addButton = document.querySelector('#builder-add');
    if (selectedItems.length < 3) {
        price.textContent = `${selectedItems.length}/3 selected`;
        addButton.disabled = true;
        return;
    }
    price.textContent = `$${(total * 0.85).toFixed(2)} · 15% saved`;
    addButton.disabled = false;
}

document.querySelector('#builder-add').addEventListener('click', () => {
    const names = Object.values(selected).map((item) => item.name).join(' + ');
    const total = Object.values(selected).reduce((sum, item) => sum + item.price, 0) * 0.85;
    showToast(`${names} added to your edit · $${total.toFixed(2)}`);
});

document.querySelectorAll('.bundle-add').forEach((button) => {
    button.addEventListener('click', () => showToast(`${button.dataset.name} added to your edit · $${Number(button.dataset.price).toFixed(2)}`));
});

document.querySelector('.newsletter').addEventListener('submit', (event) => {
    event.preventDefault();
    event.currentTarget.querySelector('button').textContent = '✓';
    event.currentTarget.querySelector('input').value = '';
});
