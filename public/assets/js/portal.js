document.querySelectorAll('[data-busy-form]').forEach(form => {
  form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (!button || !form.checkValidity()) return;
    // Keep the submitter's value in the POST body before disabling it.
    if (button.name) { const hidden = document.createElement('input'); hidden.type='hidden'; hidden.name=button.name; hidden.value=button.value; form.append(hidden); }
    button.disabled = true;
    button.textContent = 'Memproses…';
    form.setAttribute('aria-busy','true');
  });
});
document.querySelectorAll('[data-consent-form]').forEach(form => {
  const button = form.querySelector('button[type="submit"]');
  const sync = () => { button.disabled = !form.checkValidity(); };
  form.addEventListener('input', sync); form.addEventListener('change', sync); sync();
});
const expiry = document.querySelector('[data-exam-expiry]');
if (expiry) {
  const end = new Date(expiry.dataset.examExpiry).getTime();
  const tick = () => {
    const seconds = Math.max(0, Math.floor((end-Date.now())/1000));
    expiry.textContent = seconds ? `Sisa waktu ${Math.floor(seconds/60)}:${String(seconds%60).padStart(2,'0')}` : 'Waktu habis. Kirim jawaban untuk melihat hasil.';
  };
  tick(); setInterval(tick,1000);
}
