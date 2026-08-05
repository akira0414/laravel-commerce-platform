

let cart = JSON.parse(localStorage.getItem('commerce-cart') || '[]');
const money = value => 'NT$ ' + Math.round(value / 100).toLocaleString('zh-TW');
const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));

function saveCart() {
    localStorage.setItem('commerce-cart', JSON.stringify(cart));
    renderCart();
}

function renderCart() {
    const container = document.getElementById('items');
    const summary = document.getElementById('summary');
    if (!cart.length) {
        container.innerHTML = '<div class="empty">購物車目前沒有商品。<br><a href="/">返回商店選購</a></div>';
        summary.style.display = 'none';
        return;
    }
    summary.style.display = 'flex';
    container.innerHTML = cart.map(item => `<div class="item"><div><b>${escapeHtml(item.name)}</b><br><small>${escapeHtml(item.sku)}</small></div><div class="qty"><button class="btn" data-action="minus" data-id="${item.id}">−</button><b>${item.quantity}</b><button class="btn" data-action="plus" data-id="${item.id}">＋</button></div><div><b>${money(item.price * item.quantity)}</b><br><button class="remove" data-action="remove" data-id="${item.id}">移除</button></div></div>`).join('');
    document.getElementById('total').textContent = money(cart.reduce((sum, item) => sum + item.price * item.quantity, 0));
}

document.getElementById('items').addEventListener('click', event => {
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const index = cart.findIndex(item => item.id === Number(button.dataset.id));
    if (index < 0) return;
    if (button.dataset.action === 'plus' && cart[index].quantity < cart[index].stock) cart[index].quantity += 1;
    if (button.dataset.action === 'minus') cart[index].quantity -= 1;
    if (button.dataset.action === 'remove' || cart[index].quantity < 1) cart.splice(index, 1);
    saveCart();
});

renderCart();




