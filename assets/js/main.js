/* MediFindr — Global JavaScript Utilities */

// ─── THEME ────────────────────────────────────────────────────────────────────
const Theme = {
  init() {
    const saved = localStorage.getItem('mf_theme') || 'light';
    this.apply(saved);
  },
  apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('mf_theme', theme);
    const btn = document.getElementById('themeToggle');
    if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  },
  toggle() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    this.apply(current === 'dark' ? 'light' : 'dark');
  }
};

Theme.init();

// ─── TOAST ────────────────────────────────────────────────────────────────────
const Toast = {
  container: null,
  init() {
    if (!document.getElementById('toast-container')) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      document.body.appendChild(this.container);
    } else {
      this.container = document.getElementById('toast-container');
    }
  },
  show(message, type = 'success', duration = 3500) {
    if (!this.container) this.init();
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <span style="font-size:1.1rem;color:var(--clr-${type === 'success' ? 'accent' : type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'info'})">${icons[type] || '•'}</span>
      <span style="flex:1">${message}</span>
      <button onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem">×</button>
    `;
    this.container.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('removing');
      setTimeout(() => toast.remove(), 300);
    }, duration);
  },
  success(msg) { this.show(msg, 'success'); },
  error(msg)   { this.show(msg, 'error', 5000); },
  warning(msg) { this.show(msg, 'warning'); },
  info(msg)    { this.show(msg, 'info'); }
};

Toast.init();

// ─── API ──────────────────────────────────────────────────────────────────────
const API = {
  async request(url, options = {}) {
    try {
      const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json', ...options.headers },
        ...options
      });
      const data = await response.json();
      return data;
    } catch (err) {
      console.error('API Error:', err);
      return { success: false, message: 'Network error. Please try again.' };
    }
  },
  get(url)          { return this.request(url); },
  post(url, body)   { return this.request(url, { method: 'POST',   body: JSON.stringify(body) }); },
  put(url, body)    { return this.request(url, { method: 'PUT',    body: JSON.stringify(body) }); },
  delete(url)       { return this.request(url, { method: 'DELETE' }); }
};

// ─── MODAL ────────────────────────────────────────────────────────────────────
const Modal = {
  open(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
  },
  close(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
    });
    document.body.style.overflow = '';
  }
};

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});

// ─── LOADING ──────────────────────────────────────────────────────────────────
const Loading = {
  overlay: null,
  show() {
    if (!this.overlay) {
      this.overlay = document.createElement('div');
      this.overlay.className = 'loading-overlay';
      this.overlay.innerHTML = '<div class="spinner spinner-dark" style="width:36px;height:36px;border-width:3px"></div>';
      document.body.appendChild(this.overlay);
    }
    this.overlay.style.display = 'flex';
  },
  hide() {
    if (this.overlay) this.overlay.style.display = 'none';
  }
};

// ─── FORM HELPERS ─────────────────────────────────────────────────────────────
const Form = {
  setLoading(btn, loading, originalText) {
    if (loading) {
      btn.disabled = true;
      btn.dataset.orig = btn.innerHTML;
      btn.innerHTML = '<span class="spinner"></span> ' + (originalText || 'Processing...');
    } else {
      btn.disabled = false;
      btn.innerHTML = btn.dataset.orig || btn.innerHTML;
    }
  },
  clearErrors(form) {
    form.querySelectorAll('.field-error').forEach(el => el.remove());
    form.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));
  },
  showError(fieldName, msg, form) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    if (field) {
      field.classList.add('error');
      const err = document.createElement('div');
      err.className = 'field-error';
      err.textContent = msg;
      field.parentNode.appendChild(err);
    }
  },
  getData(form) {
    const fd = new FormData(form);
    const obj = {};
    for (let [k, v] of fd.entries()) obj[k] = v;
    return obj;
  }
};

// ─── SIDEBAR TOGGLE ───────────────────────────────────────────────────────────
function initSidebar() {
  const toggle    = document.querySelector('.menu-toggle');
  const sidebar   = document.querySelector('.sidebar');
  const overlay   = document.querySelector('.sidebar-overlay');
  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay && overlay.classList.toggle('open');
  });

  overlay && overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
  });
}

initSidebar();

// ─── ACTIVE NAV ───────────────────────────────────────────────────────────────
function setActiveNav() {
  const path = window.location.pathname;
  document.querySelectorAll('.sidebar-nav a').forEach(a => {
    a.classList.remove('active');
    if (a.getAttribute('href') && path.endsWith(a.getAttribute('href').split('/').pop())) {
      a.classList.add('active');
    }
  });
}

setActiveNav();

// ─── CONFIRM DIALOG ───────────────────────────────────────────────────────────
function confirmAction(message, onConfirm) {
  const existing = document.getElementById('confirm-modal');
  if (existing) existing.remove();

  const html = `
    <div class="modal-overlay open" id="confirm-modal">
      <div class="modal" style="max-width:400px">
        <div class="modal-header">
          <h3>⚠️ Confirm Action</h3>
          <button class="modal-close" onclick="document.getElementById('confirm-modal').remove()">×</button>
        </div>
        <div class="modal-body">
          <p style="color:var(--text-primary)">${message}</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" onclick="document.getElementById('confirm-modal').remove()">Cancel</button>
          <button class="btn btn-danger" id="confirm-ok-btn">Confirm</button>
        </div>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML('beforeend', html);
  document.getElementById('confirm-ok-btn').addEventListener('click', () => {
    document.getElementById('confirm-modal').remove();
    onConfirm();
  });
}

// ─── TIME AGO (client-side) ───────────────────────────────────────────────────
function timeAgo(dateStr) {
  const now = new Date();
  const then = new Date(dateStr);
  const diff = Math.floor((now - then) / 1000);
  if (diff < 60)     return 'just now';
  if (diff < 3600)   return Math.floor(diff / 60) + 'm ago';
  if (diff < 86400)  return Math.floor(diff / 3600) + 'h ago';
  if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
  return then.toLocaleDateString();
}

// ─── FORMAT CURRENCY ─────────────────────────────────────────────────────────
function formatINR(amount) {
  if (!amount && amount !== 0) return '—';
  return '₹' + parseFloat(amount).toFixed(2);
}

// ─── DEBOUNCE ─────────────────────────────────────────────────────────────────
function debounce(fn, delay = 400) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

// ─── PAGINATION RENDERER ──────────────────────────────────────────────────────
function renderPagination(container, current, total, onPage) {
  container.innerHTML = '';
  if (total <= 1) return;

  const makeBtn = (label, page, disabled = false, active = false) => {
    const btn = document.createElement('button');
    btn.className = 'page-btn' + (active ? ' active' : '');
    btn.textContent = label;
    btn.disabled = disabled;
    if (!disabled) btn.addEventListener('click', () => onPage(page));
    return btn;
  };

  container.appendChild(makeBtn('‹', current - 1, current === 1));

  const delta = 2;
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      container.appendChild(makeBtn(i, i, false, i === current));
    } else if (i === current - delta - 1 || i === current + delta + 1) {
      const dots = document.createElement('span');
      dots.textContent = '…';
      dots.style.cssText = 'padding:0 4px;color:var(--text-muted);line-height:34px;font-size:0.85rem';
      container.appendChild(dots);
    }
  }

  container.appendChild(makeBtn('›', current + 1, current === total));
}
