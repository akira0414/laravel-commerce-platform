

const money = value => 'NT$ ' + Math.round(value / 100).toLocaleString('zh-TW');
let cart = JSON.parse(localStorage.getItem('commerce-cart') || '[]');

function saveCart() {
    localStorage.setItem('commerce-cart', JSON.stringify(cart));
    renderCartCount();
}

function renderCartCount() {
    const count = cart.reduce((total, item) => total + item.quantity, 0);
    const element = document.getElementById('cart-count');
    if (element) element.textContent = count;
}

document.querySelectorAll('.add').forEach(button => {
    button.addEventListener('click', () => {
        const id = Number(button.dataset.id);
        const existing = cart.find(item => item.id === id);
        if (existing && existing.quantity < existing.stock) {
            existing.quantity += 1;
        } else if (!existing) {
            cart.push({
                id,
                sku: button.dataset.sku,
                name: button.dataset.name,
                price: Number(button.dataset.price),
                stock: Number(button.dataset.stock),
                quantity: 1,
            });
        }
        saveCart();
        button.textContent = `已加入 · ${money(Number(button.dataset.price))}`;
        window.setTimeout(() => { button.textContent = '加入購物車'; }, 900);
    });
});

renderCartCount();




