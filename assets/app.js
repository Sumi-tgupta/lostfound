/* ================================
   CONFIG: LOCALHOST BACKEND
   ================================ */
const API_BASE = "http://localhost/lostfound/api/";

/**
 * apiFetch - wrapper for fetch with JSON parsing and basic error handling
 * endpoint: "login.php", "register.php", etc.
 * method: 'GET'|'POST' etc
 * data: object for JSON body
 */
async function apiFetch(endpoint, method = 'GET', data = null) {
  const url = API_BASE + endpoint; // 🔥 Always use Localhost backend

  const opts = { 
    method, 
    credentials: 'include',        // 🔥 Needed for PHP session cookies
    headers: {} 
  };

  if (data !== null) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(data);
  }

  try {
    const res = await fetch(url, opts);
    const text = await res.text();

    let json = null;
    try { 
      json = text ? JSON.parse(text) : {}; 
    } catch(e) { 
      json = { error: 'Invalid JSON from server', raw: text }; 
    }

    if (!res.ok) {
      return { ok: false, status: res.status, ...json };
    }
    return { ok: true, status: res.status, ...json };

  } catch (err) {
    return { ok: false, error: 'Network error', details: err.message };
  }
}

/* ------------------------------
   USER SESSION HANDLING
   ------------------------------ */
function getCurrentUser() {
  try {
    const u = localStorage.getItem('user');
    if (!u) return null;
    return JSON.parse(u);
  } catch (e) {
    return null;
  }
}

function requireLogin(redirectTo = 'index.html') {
  const u = getCurrentUser();
  if (!u) {
    localStorage.setItem('afterLogin', window.location.pathname + window.location.search);
    window.location.href = redirectTo;
    return false;
  }
  return true;
}

async function logout() {
  await apiFetch('logout.php', 'POST');
  localStorage.removeItem('user');
  window.location.href = 'index.html';
}

/* ------------------------------
   UI MESSAGES
   ------------------------------ */

function showMessage(elem, message, type = 'info', duration = 4000) {
  let container = (typeof elem === 'string') ? document.querySelector(elem) : elem;
  if (!container) return console.log(message);

  container.textContent = message;
  container.className = 'form-message ' + type;
  container.style.opacity = '1';

  setTimeout(() => {
    container.style.opacity = '0';
  }, duration);
}

function showGlobalMessage(message, type = 'info', duration = 4000) {
  let messageContainer = document.getElementById('global-message-container');
  if (!messageContainer) {
    messageContainer = document.createElement('div');
    messageContainer.id = 'global-message-container';
    messageContainer.style.position = 'fixed';
    messageContainer.style.top = '80px';
    messageContainer.style.right = '20px';
    messageContainer.style.zIndex = '1001';
    messageContainer.style.display = 'flex';
    messageContainer.style.flexDirection = 'column';
    messageContainer.style.gap = '10px';
    document.body.appendChild(messageContainer);
  }
  
  const messageElement = document.createElement('div');
  messageElement.className = `form-message ${type}`;
  messageElement.textContent = message;
  messageElement.style.padding = '15px 20px';
  messageElement.style.borderRadius = '8px';
  messageElement.style.boxShadow = 'var(--shadow-lg)';
  messageElement.style.animation = 'slideIn 0.3s ease-out';
  
  messageContainer.appendChild(messageElement);
  
  setTimeout(() => {
    messageElement.style.opacity = '0';
    setTimeout(() => {
      messageElement.remove();
    }, 300);
  }, duration);
}

/* ------------------------------
   CATEGORY SELECT
   ------------------------------ */

async function populateCategorySelect(selector) {
  const sel = document.querySelector(selector);
  if (!sel) return;

  try {
    const res = await apiFetch('get_items.php?action=categories');
    
    if (res.ok && Array.isArray(res.categories) && res.categories.length > 0) {
      sel.innerHTML = '<option value="">-- Select category --</option>';
      res.categories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        sel.appendChild(opt);
      });
    } 
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
}

/* ------------------------------
   NAVBAR
   ------------------------------ */
function populateNav() {
  const u = getCurrentUser();
  const el = document.getElementById('nav-user');
  if (!el) return;

  let navHTML = '';

  if (u) {
    navHTML = `
      <div class="nav-links">
        <a href="dashboard.html">Dashboard</a>
        <a href="report.html">Report Item</a>
        ${u.Role === 'Admin' ? `<a href="admin.html">Admin</a>` : ''}
      </div>
      <div class="nav-user-info">
        <span>Hello, <strong>${escapeHtml(u.Name)}</strong></span>
        <button onclick="logout()">Logout</button>
      </div>
    `;
  } else {
    navHTML = `
      <div class="nav-links">
        <a href="index.html">Home</a>
      </div>
      <div class="nav-user-info">
        <a href="index.html">Login</a>
      </div>
    `;
  }

  el.innerHTML = navHTML;
}

/* ------------------------------ */
function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, m => ({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#39;'
  }[m]));
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString(undefined, {year:'numeric', month:'short', day:'numeric'});
}

/* ------------------------------ */
// Expose globally
window.apiFetch = apiFetch;
window.getCurrentUser = getCurrentUser;
window.requireLogin = requireLogin;
window.logout = logout;
window.showMessage = showMessage;
window.showGlobalMessage = showGlobalMessage;
window.populateCategorySelect = populateCategorySelect;
window.populateNav = populateNav;
window.escapeHtml = escapeHtml;
window.formatDate = formatDate;

