/**
 * apiFetch - wrapper for fetch with JSON parsing and basic error handling
 * path: string like 'api/login.php' or '/lostfound/api/login.php'
 * method: 'GET'|'POST' etc
 * data: object for JSON body
 */
async function apiFetch(path, method = 'GET', data = null) {
  const opts = { method, credentials: 'same-origin', headers: {} };
  if (data !== null) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(data);
  }
  try {
    const res = await fetch(path, opts);
    const text = await res.text();
    // try parse JSON safely
    let json = null;
    try { json = text ? JSON.parse(text) : {}; } catch(e) { json = { error: 'Invalid JSON from server', raw: text }; }
    if (!res.ok) {
      return { ok: false, status: res.status, ...json };
    }
    return { ok: true, status: res.status, ...json };
  } catch (err) {
    return { ok: false, error: 'Network error', details: err.message };
  }
}

/**
 * getCurrentUser - reads local storage value set after login
 * Note: session is also maintained server-side via PHP session cookie.
 */
function getCurrentUser() {
  try {
    const u = localStorage.getItem('user');
    if (!u) return null;
    return JSON.parse(u);
  } catch (e) {
    return null;
  }
}

/**
 * requireLogin - if not logged in, redirect to index.html
 * pass optional redirectTo to go there after login
 */
function requireLogin(redirectTo = 'index.html') {
  const u = getCurrentUser();
  if (!u) {
    // remember desired page
    localStorage.setItem('afterLogin', window.location.pathname + window.location.search);
    window.location.href = redirectTo;
    return false;
  }
  return true;
}

/**
 * logout - calls api/logout.php and clears localStorage user
 */
async function logout() {
  const r = await apiFetch('api/logout.php', 'POST');
  localStorage.removeItem('user');
  window.location.href = 'index.html';
}

/**
 * showMessage - small helper to show a message inside an element
 * elem: element or selector, message: string, duration ms optional
 */
function showMessage(elem, message, type = 'info', duration = 4000) {
  let container = (typeof elem === 'string') ? document.querySelector(elem) : elem;
  if (!container) {
    console.log('Message:', message);
    return;
  }
  
  // Set message text
  container.textContent = message;
  
  // Set class based on type
  container.className = 'form-message ' + type;
  
  // Make visible
  container.style.opacity = '1';
  
  // Hide after duration
  setTimeout(() => {
    container.style.opacity = '0';
  }, duration);
}


/**
 * populateCategorySelect - fetches categories and fills a <select> element
 * selector - css selector for select element
 */
async function populateCategorySelect(selector) {
  const sel = document.querySelector(selector);
  if (!sel) {
    console.error('Category select element not found:', selector);
    return;
  }
  
  try {
    const res = await apiFetch('api/get_items.php?action=categories');
    
    if (res.ok && Array.isArray(res.categories) && res.categories.length > 0) {
      // Clear existing options
      sel.innerHTML = '<option value="">-- Select category --</option>';
      
      // Add categories from API
      res.categories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        sel.appendChild(opt);
      });
      
      console.log('Categories successfully loaded:', res.categories);
    } else {
      console.warn('API returned no categories, using fallback');
      // Add fallback categories
      const fallbackCategories = [
        'Electronics', 'Clothing', 'Books', 'Accessories', 
        'Documents', 'Jewelry', 'Keys', 'Bags', 'Others'
      ];
      
      sel.innerHTML = '<option value="">-- Select category --</option>';
      fallbackCategories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        sel.appendChild(opt);
      });
    }
  } catch (error) {
    console.error('Error fetching categories:', error);
    // Add fallback categories on error
    const fallbackCategories = [
      'Electronics', 'Clothing', 'Books', 'Accessories', 
      'Documents', 'Jewelry', 'Keys', 'Bags', 'Others'
    ];
    
    sel.innerHTML = '<option value="">-- Select category --</option>';
    fallbackCategories.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c;
      opt.textContent = c;
      sel.appendChild(opt);
    });
  }
}

/**
 * populateNav - populates navigation with links and user info
 * Includes Dashboard, Report, and Admin (for admin users) links
 */
function populateNav() {
  const u = getCurrentUser();
  const el = document.getElementById('nav-user');
  if (!el) return;
  
  let navHTML = '';
  
  // Navigation links (show if user is logged in)
  if (u) {
    navHTML = `
      <div class="nav-links">
        <a href="dashboard.html" class="nav-link ${window.location.pathname.includes('dashboard') ? 'active' : ''}">Dashboard</a>
        <a href="report.html" class="nav-link ${window.location.pathname.includes('report') ? 'active' : ''}">Report Item</a>
        ${u.Role === 'Admin' ? `<a href="admin.html" class="nav-link ${window.location.pathname.includes('admin') ? 'active' : ''}">Admin</a>` : ''}
      </div>
      <div class="nav-user-info">
        <span class="welcome-text">Hello, <strong>${escapeHtml(u.Name || u.name || '')}</strong></span>
        <button class="logout-btn" onclick="logout()">Logout</button>
      </div>
    `;
  } else {
    navHTML = `
      <div class="nav-links">
        <a href="index.html" class="nav-link ${window.location.pathname.includes('index') ? 'active' : ''}">Home</a>
      </div>
      <div class="nav-user-info">
        <a href="index.html" class="login-link">Login</a>
      </div>
    `;
  }
  
  el.innerHTML = navHTML;
}

/** small escape to avoid injection into nav */
function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]; });
}

/**
 * formatDate - format date string to readable format
 */
function formatDate(dateString) {
  if (!dateString) return 'N/A';
  
  const date = new Date(dateString);
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return date.toLocaleDateString(undefined, options);
}

// expose a few functions globally so pages can call them
window.apiFetch = apiFetch;
window.getCurrentUser = getCurrentUser;
window.requireLogin = requireLogin;
window.logout = logout;
window.showMessage = showMessage;
window.populateCategorySelect = populateCategorySelect;
window.populateNav = populateNav;
window.escapeHtml = escapeHtml;
window.formatDate = formatDate;