document.addEventListener('DOMContentLoaded', () => {

  /* ---- Confirm on dangerous forms ---- */
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!window.confirm(form.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  /* ---- Non-negative number inputs ---- */
  document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', () => {
      input.setCustomValidity(
        (input.validity.badInput || +input.value < 0) ? 'Enter a valid non-negative value.' : ''
      );
    });
  });

  /* ---- Theme toggle ---- */
  const root        = document.documentElement;
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon   = document.getElementById('themeIcon');
  const themeLabel  = document.getElementById('themeLabel');
  const STORAGE_KEY = 'stocker-theme';

  function applyTheme(theme, save) {
    const dark = theme === 'dark';
    root.setAttribute('data-theme', dark ? 'dark' : 'light');
    if (themeIcon)  themeIcon.textContent  = dark ? '\u2600' : '\u263D';
    if (themeLabel) themeLabel.textContent = dark ? 'Light' : 'Dark';
    if (themeToggle) themeToggle.setAttribute('aria-pressed', String(dark));
    if (save) { try { localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light'); } catch(_){} }
  }

  applyTheme(root.getAttribute('data-theme') || 'light');
  if (themeToggle) themeToggle.addEventListener('click', () => {
    applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
  });

  /* ---- Mobile nav drawer ---- */
  const menuToggle  = document.getElementById('menuToggle');
  const navDrawer   = document.getElementById('navDrawer');
  const drawerScrim = document.getElementById('drawerScrim');

  const openDrawer = () => {
    navDrawer   && navDrawer.classList.add('open');
    drawerScrim && (drawerScrim.style.display = 'block');
    menuToggle  && menuToggle.setAttribute('aria-expanded', 'true');
  };
  const closeDrawer = () => {
    navDrawer   && navDrawer.classList.remove('open');
    drawerScrim && (drawerScrim.style.display = 'none');
    menuToggle  && menuToggle.setAttribute('aria-expanded', 'false');
  };

  if (menuToggle) menuToggle.addEventListener('click', () =>
    navDrawer && navDrawer.classList.contains('open') ? closeDrawer() : openDrawer()
  );
  if (drawerScrim) drawerScrim.addEventListener('click', closeDrawer);
  if (navDrawer) navDrawer.querySelectorAll('a').forEach(l => l.addEventListener('click', closeDrawer));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
  window.addEventListener('resize', () => { if (window.innerWidth > 860) closeDrawer(); });
});
