

const cart = JSON.parse(localStorage.getItem('commerce-cart') || '[]');
const money = value => 'NT$ ' + Math.round(value / 100).toLocaleString('zh-TW');
const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
const items = document.getElementById('items');
const button = document.getElementById('pay-button');

if (!cart.length) {
    items.innerHTML = '<div class="empty">購物車目前沒有商品。<br><a href="/">返回商店選購</a></div>';
    button.disabled = true;
} else {
    items.innerHTML = cart.map(item => `<div class="item"><div><b>${escapeHtml(item.name)}</b><small>${escapeHtml(item.sku)} × ${Number(item.quantity)}</small></div><b>${money(item.price * item.quantity)}</b></div>`).join('');
    document.getElementById('total').textContent = money(cart.reduce((sum, item) => sum + item.price * item.quantity, 0));
}

document.getElementById('checkout-form').addEventListener('submit', async event => {
    event.preventDefault();
    const message = document.getElementById('message');
    button.disabled = true;
    button.textContent = '正在安全處理付款…';
    message.textContent = '';
    try {
        const response = await fetch('/checkout', {
            method: 'POST',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify({
                shipping_address: {recipient:document.getElementById('recipient').value,phone:document.getElementById('phone').value,address:document.getElementById('address').value},
                payment_method: document.getElementById('payment_method').value,
                items: cart.map(item => ({product_id:item.id,quantity:item.quantity})),
            }),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || '付款失敗，請稍後再試。');
        localStorage.removeItem('commerce-cart');
        document.getElementById('checkout-page').innerHTML = `<section class="card success"><div class="success-mark">✓</div><h1>付款完成</h1><p>訂單 <b>${escapeHtml(data.number)}</b> 已成立。</p><a href="${escapeHtml(data.account_url)}">查看我的訂單</a></section>`;
    } catch (error) {
        message.textContent = error.message;
        button.disabled = false;
        button.textContent = '確認訂單並付款';
    }
});




