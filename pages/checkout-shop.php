<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();

$id = (int)get('id');
$st = db()->prepare("SELECT * FROM shop_items WHERE id = ?");
$st->execute([$id]);
$product = $st->fetch();

if (!$product) {
    flash('err', 'Product nahi mila.');
    redirect('pages/shop.php');
}

$pageTitle = 'Secure Shop Checkout';
include dirname(__DIR__) . '/includes/header.php';
?>

<style>
.checkout-shell{max-width:1060px;margin:0 auto;padding:54px 24px 90px}
.checkout-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);gap:26px;align-items:start}
.checkout-card{background:var(--bg3);border:1px solid var(--line);border-radius:20px;padding:30px;box-shadow:var(--shadow)}
.checkout-step{font:700 12px var(--tech);letter-spacing:1.5px;text-transform:uppercase;color:var(--molten)}
.checkout-card h1{margin:10px 0 8px;font-size:clamp(26px,3.6vw,38px);color: var(--text-1);}
.checkout-card p{color:var(--muted);line-height:1.65}
.checkout-summary{position:sticky;top:104px}
.checkout-row{display:flex;justify-content:space-between;gap:16px;padding:12px 0;color:var(--muted);font-size:14.5px}
.checkout-row strong{color:var(--text)}
.checkout-total{border-top:1px solid var(--line);margin-top:10px;padding-top:18px;font-size:19px}
.checkout-lock{display:flex;gap:9px;align-items:center;margin-top:18px;font-size:12px;color:var(--muted)}
.sandbox-pill{display:inline-flex;align-items:center;gap:7px;background:rgba(255,184,0,.12);border:1px solid rgba(255,184,0,.35);color: var(--gold-text);font:700 11px var(--tech);letter-spacing:1.2px;text-transform:uppercase;border-radius:999px;padding:5px 12px;margin-bottom:14px}
.pay-field{margin-bottom:16px}
.pay-field label{display:block;font:700 11.5px var(--tech);letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.pay-field input, .pay-field select{width:100%;padding:13px 15px;border-radius:12px;border:1.5px solid var(--line);background:var(--bg);color:var(--text);font-size:15px;transition:border-color .2s, box-shadow .2s;outline:none}
.pay-field input:focus, .pay-field select:focus{border-color:var(--molten);box-shadow:0 0 0 3px rgba(255,107,26,.15)}
.pay-field input.err{border-color:var(--danger);box-shadow:0 0 0 3px rgba(229,72,77,.12)}
.pay-field .hint{font-size:12px;color: var(--danger-text);margin-top:5px;display:none}
.pay-field.show-err .hint{display:block}
.pay-split{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.card-wrap{position:relative}
.card-brand{position:absolute;right:13px;top:50%;transform:translateY(-50%);font-size:19px;opacity:.85}
.pay-btn{width:100%;justify-content:center;position:relative;cursor: pointer;}
.pay-btn[disabled]{opacity:.7;cursor:progress}
.pay-spin{display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:var(--on-media);border-radius:50%;animation:paySpin .7s linear infinite;margin-right:9px}
.pay-btn.busy .pay-spin{display:inline-block}
@keyframes paySpin{to{transform:rotate(360deg)}}
.pm-icons{display:flex;gap:8px;margin-top:16px;align-items:center;opacity:.75}
.pm-icons span{font-size:11px;color:var(--muted);letter-spacing:.6px}
@media(max-width:820px){.checkout-grid{grid-template-columns:1fr}.checkout-summary{position:static;order:-1}.checkout-shell{padding:34px 16px 64px}}
</style>

<section class="checkout-shell">
  <div class="checkout-grid">
    <article class="checkout-card">
      <span class="checkout-step">Secure Checkout</span>
      <h1>Buy Equipment</h1>
      <p>Signed in as <b><?= e(current_user()['email']) ?></b>. Items will be dispatched within 24 hours.</p>

      <div style="margin-top:18px">
        <span class="sandbox-pill">⚡ Secure Checkout — Payment via Gemini Pay</span>
        <form id="sandboxPay" method="post" action="<?= url('api/buy-item.php') ?>" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="item_id" value="<?= e($product['id']) ?>">

          <div class="pay-field">
            <label for="ccName">Full Name</label>
            <input id="ccName" name="name" type="text" placeholder="MUHAMMAD ALI" spellcheck="false" required value="<?= e(current_user()['name']) ?>">
            <div class="hint">Please enter your full name.</div>
          </div>

          <div class="pay-field">
            <label for="ccAddress">Shipping Address</label>
            <input id="ccAddress" name="address" type="text" placeholder="House 4B, Street 12, DHA Phase 6, Karachi" spellcheck="false" required>
            <div class="hint">Shipping address likhein.</div>
          </div>

          <div class="pay-field">
            <label for="payMethod">Payment Method</label>
            <select id="payMethod" name="payment_method">
              <option value="Gemini Pay" selected>⚡ Gemini Secure Pay (Recommended)</option>
              <option value="Credit/Debit Card">💳 Credit / Debit Card (Sandbox)</option>
              <option value="Cash on Delivery">📦 Cash on Delivery</option>
            </select>
          </div>

          <div id="cardSection">
            <div class="pay-field">
              <label for="ccNum">Card number</label>
              <div class="card-wrap">
                <input id="ccNum" type="text" inputmode="numeric" placeholder="4242 4242 4242 4242" maxlength="19">
                <span class="card-brand" id="ccBrand">💳</span>
              </div>
              <div class="hint">Invalid card number (Luhn verify fail).</div>
            </div>

            <div class="pay-split">
              <div class="pay-field">
                <label for="ccExp">Expiry</label>
                <input id="ccExp" type="text" inputmode="numeric" placeholder="MM/YY" maxlength="5">
                <div class="hint">Future date required.</div>
              </div>
              <div class="pay-field">
                <label for="ccCvc">CVC</label>
                <input id="ccCvc" type="text" inputmode="numeric" placeholder="123" maxlength="4">
                <div class="hint">3-4 digits.</div>
              </div>
            </div>
          </div>

          <button class="btn btn-fire pay-btn" id="payBtn" type="submit">
            <span class="pay-spin"></span><span id="payLabel">Confirm Order · $<?= number_format($product['price'], 2) ?></span>
          </button>
        </form>
        <div class="pm-icons"><span>Method:</span> 🤖 Gemini Pay · 💳 Visa/Mastercard · 📦 Cash on Delivery</div>
        <p class="checkout-lock">🔒 Purchases are secured with modern end-to-end tokenization protocol.</p>
      </div>
    </article>

    <aside class="checkout-card checkout-summary">
      <span class="checkout-step">Order Summary</span>
      <h2 style="margin:10px 0 18px; color: var(--text-1);"><?= e($product['name']) ?></h2>
      
      <div style="display: flex; gap: 15px; margin-bottom: 20px;">
        <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px; border: 1px solid var(--line);">
        <div>
          <p style="font-size: 13px; line-height: 1.4; color: var(--muted);"><?= e($product['description']) ?></p>
        </div>
      </div>

      <div class="checkout-row"><span>Unit Price</span><strong>$<?= number_format($product['price'], 2) ?></strong></div>
      <div class="checkout-row"><span>Quantity</span><strong>1x</strong></div>
      <div class="checkout-row"><span>Shipping</span><strong>$0.00 (Free)</strong></div>
      <div class="checkout-row checkout-total"><strong>Total Amount</strong><strong>$<?= number_format($product['price'], 2) ?></strong></div>
      
      <a href="<?= url('pages/shop.php') ?>" style="display:block;text-align:center;margin-top:22px;color:var(--gold-text)">← Back to Shop</a>
    </aside>
  </div>
</section>

<script>
(function () {
  const form = document.getElementById('sandboxPay'), num = document.getElementById('ccNum'), exp = document.getElementById('ccExp'), cvc = document.getElementById('ccCvc'), name = document.getElementById('ccName'), address = document.getElementById('ccAddress'), brand = document.getElementById('ccBrand'), payMethod = document.getElementById('payMethod'), cardSection = document.getElementById('cardSection');

  // Toggle card inputs based on selection
  payMethod.addEventListener('change', () => {
    if (payMethod.value === 'Credit/Debit Card') {
      cardSection.style.display = 'block';
    } else {
      cardSection.style.display = 'none';
      // clear errors
      fieldOk(num);
      fieldOk(exp);
      fieldOk(cvc);
    }
  });

  // initial state
  if (payMethod.value !== 'Credit/Debit Card') {
    cardSection.style.display = 'none';
  }

  const digits = (s) => s.replace(/\D/g, '');
  const luhn = (s) => {
    let sum = 0, dbl = false;
    for (let i = s.length - 1; i >= 0; i--) {
      let d = +s[i];
      if (dbl) { d *= 2; if (d > 9) d -= 9; }
      sum += d; dbl = !dbl;
    }
    return s.length >= 13 && sum % 10 === 0;
  };

  num.addEventListener('input', () => {
    const d = digits(num.value).slice(0, 16);
    num.value = d.replace(/(.{4})/g, '$1 ').trim();
    brand.textContent = d.startsWith('4') ? '🟦 Visa' : /^5[1-5]/.test(d) ? '🟠 MC' : d.startsWith('62') ? '🔵 UP' : '💳';
    brand.style.fontSize = d ? '12px' : '19px';
    fieldOk(num);
  });
  exp.addEventListener('input', () => {
    let d = digits(exp.value).slice(0, 4);
    if (d.length >= 3) d = d.slice(0, 2) + '/' + d.slice(2);
    exp.value = d;
    fieldOk(exp);
  });
  cvc.addEventListener('input', () => { cvc.value = digits(cvc.value).slice(0, 4); fieldOk(cvc); });
  name.addEventListener('input', () => fieldOk(name));
  address.addEventListener('input', () => fieldOk(address));

  function fieldOk(input) { input.classList.remove('err'); input.closest('.pay-field').classList.remove('show-err'); }
  function fieldErr(input) { input.classList.add('err'); input.closest('.pay-field').classList.add('show-err'); return false; }

  function expValid(v) {
    const m = v.match(/^(\d{2})\/(\d{2})$/);
    if (!m) return false;
    const mm = +m[1], yy = 2000 + +m[2];
    if (mm < 1 || mm > 12) return false;
    const now = new Date();
    return yy > now.getFullYear() || (yy === now.getFullYear() && mm >= now.getMonth() + 1);
  }

  form.addEventListener('submit', (ev) => {
    let ok = true;
    if (name.value.trim().length < 3) ok = fieldErr(name);
    if (address.value.trim().length < 10) ok = fieldErr(address);
    
    if (payMethod.value === 'Credit/Debit Card') {
      if (!luhn(digits(num.value))) ok = fieldErr(num);
      if (!expValid(exp.value)) ok = fieldErr(exp);
      if (cvc.value.length < 3) ok = fieldErr(cvc);
    }
    
    if (!ok) { ev.preventDefault(); return; }
    
    if (!form.dataset.processing) {
      ev.preventDefault();
      form.dataset.processing = '1';
      const btn = document.getElementById('payBtn');
      btn.disabled = true; btn.classList.add('busy');
      document.getElementById('payLabel').textContent = 'Confirming order via ' + payMethod.value + '...';
      setTimeout(() => form.submit(), 1200);
    }
  });
})();
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
