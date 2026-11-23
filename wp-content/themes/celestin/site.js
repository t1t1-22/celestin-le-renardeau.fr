document.querySelectorAll('.mobile-menu-toggle').forEach(button => {
  button.addEventListener('click', () => {
    const nav = document.getElementById(button.getAttribute('aria-controls'));
    const opened = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!opened));
    nav.classList.toggle('menu-open', !opened);
  });
});
document.querySelectorAll('.main-nav a').forEach(link => {
  link.addEventListener('click', () => {
    document.querySelector('.main-nav')?.classList.remove('menu-open');
    document.querySelector('.mobile-menu-toggle')?.setAttribute('aria-expanded', 'false');
  });
});
