/* Yogitech front-end behaviour (no framework, progressive enhancement). */
(() => {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const csrf = $('meta[name="csrf-token"]')?.content || '';
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function getJSON(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }

  /* Wordmark glitch: one orchestrated moment on page load */
  const mark = $('.wordmark[data-glitch]');
  if (mark && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    setTimeout(() => mark.classList.add('is-glitching'), 350);
  }

  /* Dropdowns */
  $$('[data-dropdown]').forEach((dd) => {
    const btn = $('[data-dropdown-toggle]', dd);
    btn?.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = dd.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open);
    });
  });
  document.addEventListener('click', () => $$('[data-dropdown].is-open').forEach((d) => d.classList.remove('is-open')));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('[data-dropdown].is-open').forEach((d) => d.classList.remove('is-open')); });

  /* Flash messages */
  $$('.flash').forEach((f) => {
    $('button', f)?.addEventListener('click', () => f.remove());
    setTimeout(() => f.remove(), 7000);
  });

  /* Live search (calls GET /api/products?q=) */
  const search = $('#search-input');
  const box = $('#search-results');
  if (search && box) {
    let timer, active = -1, controller;
    const close = () => { box.classList.remove('is-open'); active = -1; };
    search.addEventListener('input', () => {
      clearTimeout(timer);
      const q = search.value.trim();
      if (q.length < 2) return close();
      timer = setTimeout(async () => {
        controller?.abort();
        controller = new AbortController();
        try {
          const res = await fetch(search.dataset.endpoint + '?limit=6&q=' + encodeURIComponent(q), { signal: controller.signal, headers: { Accept: 'application/json' } });
          const json = await res.json();
          if (!json.data.length) {
            box.innerHTML = `<div class="search__all muted">No gadgets match “${esc(q)}”. Try a brand like Apple or Samsung.</div>`;
          } else {
            box.innerHTML = json.data.map((p) => `
              <a class="search__item" href="${esc(p.url)}">
                <img src="${esc(p.thumbnail)}" alt="" loading="lazy" onerror="this.style.visibility='hidden'">
                <span><b>${esc(p.name)}</b><span class="price small">${esc(p.price_label)}</span> <span class="dim small">${esc(p.category || '')}</span></span>
              </a>`).join('') +
              `<a class="search__all" href="${esc(search.form.action)}?q=${encodeURIComponent(q)}">See all ${json.meta.total} result(s)</a>`;
          }
          box.classList.add('is-open');
          active = -1;
        } catch (err) { if (err.name !== 'AbortError') close(); }
      }, 220);
    });
    search.addEventListener('keydown', (e) => {
      const items = $$('.search__item', box);
      if (!box.classList.contains('is-open') || !items.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        active = (active + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        items.forEach((it, i) => it.classList.toggle('is-active', i === active));
      } else if (e.key === 'Enter' && active >= 0) {
        e.preventDefault();
        location.href = items[active].href;
      } else if (e.key === 'Escape') close();
    });
    document.addEventListener('click', (e) => { if (!box.contains(e.target) && e.target !== search) close(); });
  }

  /* Wishlist toggle (POST /wishlist/{id}/toggle as JSON) */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.js-wish');
    if (!btn) return;
    e.preventDefault();
    if (btn.dataset.guest) { location.href = btn.dataset.guest; return; }
    btn.disabled = true;
    try {
      const res = await fetch(btn.dataset.url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } });
      if (res.status === 401) { location.href = btn.dataset.login; return; }
      const json = await res.json();
      btn.classList.toggle('is-saved', json.saved);
      btn.setAttribute('aria-pressed', json.saved);
      const label = $('.lbl', btn);
      if (label) label.textContent = json.saved ? 'Saved' : 'Save';
    } finally { btn.disabled = false; }
  });

  /* Product gallery */
  $$('[data-gallery]').forEach((g) => {
    const main = $('[data-gallery-main]', g);
    const video = $('[data-gallery-video]', g);
    const thumbs = $$('[data-src], [data-show-video]', g);
    const activate = (t) => thumbs.forEach((x) => x.classList.toggle('is-active', x === t));
    const pauseVideo = () => {
      if (!video) return;
      const v = $('video', video);
      if (v) v.pause();
      const f = $('iframe', video);
      if (f && f.contentWindow) f.contentWindow.postMessage(JSON.stringify({ event: 'command', func: 'pauseVideo', args: [] }), '*');
    };
    $$('[data-src]', g).forEach((t) => t.addEventListener('click', () => {
      pauseVideo();
      if (video) video.hidden = true;
      main.hidden = false;
      main.src = t.dataset.src;
      activate(t);
    }));
    // YouTube loads only when the buyer presses play (faster page, nothing black while it loads)
    $$('[data-yt-src]', g).forEach((btn) => btn.addEventListener('click', () => {
      const f = document.createElement('iframe');
      f.src = btn.dataset.ytSrc;
      f.title = btn.dataset.ytTitle || 'Product video';
      f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      f.allowFullscreen = true;
      f.referrerPolicy = 'strict-origin-when-cross-origin';
      btn.replaceWith(f);
    }));
    $$('[data-show-video]', g).forEach((t) => t.addEventListener('click', () => {
      main.hidden = true;
      video.hidden = false;
      activate(t);
    }));
  });

  /* Quantity steppers */
  $$('.qty').forEach((q) => {
    const input = $('input', q);
    $$('[data-step]', q).forEach((b) => b.addEventListener('click', () => {
      const max = parseInt(input.max || '99', 10);
      const v = Math.min(max, Math.max(1, (parseInt(input.value, 10) || 1) + parseInt(b.dataset.step, 10)));
      input.value = v;
      if (q.dataset.autosubmit !== undefined) input.form.submit();
    }));
  });

  /* Auto-submit selects / inputs */
  $$('[data-autosubmit]').forEach((el) => {
    if (el.tagName === 'SELECT' || el.type === 'checkbox') el.addEventListener('change', () => el.form.submit());
  });

  /* Confirm dialogs */
  $$('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  /* Form errors: clear a field's error as soon as the user fixes it */
  const fieldOf = (input) => input.closest('.field');
  const clearError = (field) => {
    if (!field) return;
    field.classList.remove('has-error');
    $$('.error-text', field).forEach((el) => el.remove());
  };
  const showError = (field, msg) => {
    if (!field) return;
    clearError(field);
    field.classList.add('has-error');
    const span = document.createElement('span');
    span.className = 'error-text';
    span.setAttribute('role', 'alert');
    span.textContent = msg;
    field.appendChild(span);
  };
  document.addEventListener('input', (e) => { if (e.target.matches('input, select, textarea')) clearError(fieldOf(e.target)); });
  document.addEventListener('change', (e) => { if (e.target.matches('input[type=checkbox], input[type=radio], select')) clearError(fieldOf(e.target)); });

  /* Quick checks before sending (server still validates everything). Keeps typed passwords
     from being wiped just because a checkbox was missed. */
  $$('form[data-validate]').forEach((form) => form.addEventListener('submit', (e) => {
    let first = null;
    $$('input[required]:not([disabled]), select[required]:not([disabled]), textarea[required]:not([disabled])', form).forEach((input) => {
      const label = input.dataset.label || (form.querySelector(`label[for="${input.id}"]`)?.textContent || 'This field').trim();
      const empty = input.type === 'checkbox' ? !input.checked : input.value.trim() === '';
      let msg = empty ? `${label} is required.` : '';
      if (!msg && input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim())) msg = 'Enter a valid email address.';
      if (!msg && input.minLength > 0 && input.value.length < input.minLength) msg = `${label} must be at least ${input.minLength} characters.`;
      if (!msg && input.name === 'password' && !(/[A-Za-z]/.test(input.value) && /\d/.test(input.value))) msg = 'Use at least one letter and one number.';
      if (msg) { showError(fieldOf(input), msg); first = first || input; }
    });
    const pw = form.querySelector('input[name="password"]');
    const confirmPw = form.querySelector('input[name="password_confirmation"]');
    if (pw && confirmPw && confirmPw.value !== '' && pw.value !== confirmPw.value) {
      showError(fieldOf(confirmPw), 'Passwords don’t match.');
      first = first || confirmPw;
    }
    if (first) { e.preventDefault(); first.focus(); }
  }));

  /* Stop double submits (e.g. clicking "Place order" twice). Runs after the confirm() check above. */
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (e.defaultPrevented || (form.method || '').toLowerCase() !== 'post') return;
    const buttons = $$('button[type="submit"], button:not([type])', form).concat(form.id ? $$(`button[form="${form.id}"]`) : []);
    setTimeout(() => buttons.forEach((b) => { b.disabled = true; b.setAttribute('aria-busy', 'true'); }), 0);
  });
  // Re-enable buttons when the page comes back from the browser's back/forward cache.
  window.addEventListener('pageshow', () => $$('button[aria-busy="true"]').forEach((b) => { b.disabled = false; b.removeAttribute('aria-busy'); }));

  /* Checkout: payment method panels */
  const payRadios = $$('input[name="payment_method"]');
  const syncPay = () => payRadios.forEach((r) => {
    const panel = document.getElementById('pay-' + r.value);
    if (panel) {
      panel.classList.toggle('is-open', r.checked);
      $$('input', panel).forEach((i) => { i.disabled = !r.checked; });
    }
  });
  const payButton = document.querySelector('#checkout-form [data-place-order]');
  const syncButton = () => {
    const r = payRadios.find((x) => x.checked);
    if (payButton && r && r.dataset.button) payButton.lastChild.textContent = r.dataset.button;
  };
  payRadios.forEach((r) => r.addEventListener('change', () => { syncPay(); syncButton(); }));
  syncButton();
  syncPay();

  const card = $('input[name="card_number"]');
  card?.addEventListener('input', () => { card.value = card.value.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim(); });
  const exp = $('input[name="card_expiry"]');
  exp?.addEventListener('input', () => { const d = exp.value.replace(/\D/g, '').slice(0, 4); exp.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d; });

  /* Checkout: recalculate shipping when region changes (GET /checkout/quote) */
  const regionSel = $('select[data-quote-url]');
  regionSel?.addEventListener('change', async () => {
    try {
      const q = await getJSON(regionSel.dataset.quoteUrl + '?region=' + encodeURIComponent(regionSel.value));
      ['subtotal', 'shipping', 'discount', 'total', 'eta'].forEach((k) => { $$(`[data-sum="${k}"]`).forEach((el) => { el.textContent = q[k]; }); });
      const expected = $('[data-expected-total]');
      if (expected) expected.value = q.total_raw;
      q.groups.forEach((g) => { const el = $(`[data-ship-seller="${g.seller_id}"]`); if (el) el.textContent = g.shipping; });
    } catch (_) { /* server recalculates on submit anyway */ }
  });

  /* Product page: shipping estimate per region (GET /api/shipping-quote) */
  const shipSel = $('select[data-shipping-url]');
  shipSel?.addEventListener('change', async () => {
    try {
      const { data } = await getJSON(shipSel.dataset.shippingUrl + '?region=' + encodeURIComponent(shipSel.value) + '&subtotal=' + shipSel.dataset.subtotal);
      $('[data-ship-fee]').textContent = data.fee_label;
      $('[data-ship-eta]').textContent = data.eta_label;
    } catch (_) {}
  });

  /* Seller: show the right extra fields for the chosen status */
  const statusSel = $('select[data-status-select]');
  const syncStatus = () => {
    $$('[data-for-status]').forEach((el) => {
      const show = el.dataset.forStatus.split(',').includes(statusSel.value);
      el.hidden = !show;
      $$('input,select', el).forEach((i) => { i.disabled = !show; });
    });
  };
  statusSel?.addEventListener('change', syncStatus);
  if (statusSel) syncStatus();

  /* Seller product form: live image preview */
  const thumbUrl = $('input[name="thumbnail_url"]');
  const preview = $('[data-thumb-preview]');
  thumbUrl?.addEventListener('change', () => { if (thumbUrl.value && preview) preview.src = thumbUrl.value; });
  $('input[name="thumbnail_file"]')?.addEventListener('change', (e) => {
    const f = e.target.files[0];
    if (f && preview) preview.src = URL.createObjectURL(f);
  });

  /* Mobile menu */
  $('[data-menu-toggle]')?.addEventListener('click', () => $('.nav')?.classList.toggle('is-open'));
})();
