(() => {
  document.querySelectorAll('.needs-validation').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  document.querySelectorAll('.toast').forEach((toastEl) => {
    const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4500 });
    toast.show();
  });

  const mediaSrc = (value) => {
    const raw = String(value || '').trim();
    if (!raw) return '';
    try {
      const url = new URL(raw, window.location.origin);
      const index = url.pathname.indexOf('/assets/uploads/');
      if (index >= 0) return url.pathname.slice(0, index) + url.pathname.slice(index);
    } catch (_) {}
    return raw;
  };

  const setUploadPreview = (form, field, value) => {
    const preview = form.querySelector(`[data-upload-preview="${field}"]`);
    const empty = form.querySelector(`[data-upload-empty="${field}"]`);
    const src = mediaSrc(value);
    if (!preview) return;
    if (src) {
      preview.src = src;
      preview.classList.remove('d-none');
      if (empty) empty.classList.add('d-none');
    } else {
      preview.removeAttribute('src');
      preview.classList.add('d-none');
      if (empty) empty.classList.remove('d-none');
    }
  };

  const refreshUploadPreviews = (form, item = {}) => {
    form.querySelectorAll('[data-upload-preview]').forEach((preview) => {
      const field = preview.dataset.uploadPreview;
      const hiddenValue = form.querySelector(`[name="${field}"]`)?.value || '';
      const itemValue = item[field] || item[field.replace(/^existing_/, '')] || '';
      setUploadPreview(form, field, hiddenValue || itemValue);
    });
  };

  const populateEditForm = (button) => {
    const form = document.querySelector(`${button.dataset.bsTarget} form`);
    if (!form) return;
    form.reset();
    const item = JSON.parse(button.dataset.item || '{}');
    Object.entries(item).forEach(([key, value]) => {
      const field = form.querySelector(`[name="${key}"]`);
      if (!field) return;
      if (field.type === 'checkbox') {
        field.checked = value === 1 || value === '1' || value === true;
      } else {
        field.value = value ?? '';
      }
    });
    ['image', 'logo', 'profile_image'].forEach((name) => {
      const hidden = form.querySelector(`[name="existing_${name}"]`);
      if (hidden && item[name]) hidden.value = item[name];
    });
    refreshUploadPreviews(form, item);
    requestAnimationFrame(() => refreshUploadPreviews(form, item));
  };

  document.querySelectorAll('.edit-item').forEach((button) => {
    button.addEventListener('click', () => populateEditForm(button));
  });

  document.querySelectorAll('.modal').forEach((modal) => {
    modal.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      if (button?.classList.contains('edit-item')) populateEditForm(button);
    });
  });

  document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const form = input.closest('form');
      if (!form || !input.files?.[0]) return;
      const hiddenName = input.name === 'logo' ? 'existing_logo' : `existing_${input.name}`;
      if (!input.files[0].type.startsWith('image/')) return;
      setUploadPreview(form, hiddenName, URL.createObjectURL(input.files[0]));
    });
  });

  document.querySelectorAll('.modal').forEach((modal) => {
    modal.addEventListener('hidden.bs.modal', () => {
      modal.querySelector('form')?.reset();
      modal.querySelector('form')?.classList.remove('was-validated');
      modal.querySelectorAll('[data-upload-preview]').forEach((preview) => {
        preview.removeAttribute('src');
        preview.classList.add('d-none');
      });
      modal.querySelectorAll('[data-upload-empty]').forEach((empty) => empty.classList.remove('d-none'));
    });
  });

  const items = (window.__NAV_SEARCH_ITEMS__ || []).map(([title, url]) => ({
    title: String(title || ''),
    url: String(url || ''),
  }));
  const panel = document.getElementById('navSearchPanel');
  const toggle = document.getElementById('navSearchToggle');
  const desktopInput = document.getElementById('navSearchInput');
  const desktopResults = document.getElementById('navSearchResults');
  const mobileInput = document.getElementById('navSearchInputMobile');
  const mobileResults = document.getElementById('navSearchResultsMobile');

  const escapeHtml = (value) => value.replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[char]));

  const renderResults = (resultsEl, query) => {
    if (!resultsEl) return;
    const q = String(query || '').trim().toLowerCase();
    if (!q) {
      resultsEl.style.display = 'none';
      resultsEl.innerHTML = '';
      return;
    }
    const matches = items.filter((item) => item.title.toLowerCase().includes(q)).slice(0, 8);
    resultsEl.innerHTML = matches.length
      ? matches.map((item) => `<a class="nav-search-result" href="${escapeHtml(item.url)}">${escapeHtml(item.title)}</a>`).join('')
      : '<div class="nav-search-empty">No results found.</div>';
    resultsEl.style.display = 'block';
  };

  const bindSearch = (input, results) => {
    if (!input || !results) return;
    input.addEventListener('input', () => renderResults(results, input.value));
    input.addEventListener('focus', () => renderResults(results, input.value));
  };

  bindSearch(desktopInput, desktopResults);
  bindSearch(mobileInput, mobileResults);

  toggle?.addEventListener('click', (event) => {
    event.preventDefault();
    if (!panel) return;
    panel.hidden = !panel.hidden;
    toggle.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
    if (!panel.hidden) setTimeout(() => desktopInput?.focus(), 0);
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (panel && toggle && !panel.hidden && !panel.contains(target) && !toggle.contains(target)) {
      panel.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
    }
    if (mobileResults && mobileInput && !mobileResults.contains(target) && !mobileInput.contains(target)) {
      mobileResults.style.display = 'none';
    }
  });
})();
