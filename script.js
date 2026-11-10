document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"], button:not([type])');
      if (button && !form.classList.contains('no-loading')) {
        button.dataset.original = button.textContent;
        button.textContent = 'Processing...';
        button.disabled = true;
      }
    });
  });
});
