// Small helpers shared across pages.
document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss alerts after 5s
  document.querySelectorAll('.alert.auto-dismiss').forEach(a => {
    setTimeout(() => { a.classList.remove('show'); a.classList.add('fade'); }, 5000);
  });
});
