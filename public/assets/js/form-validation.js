document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-consent-gated-form]').forEach((form) => {
    const submitButton = form.querySelector('[type="submit"]');
    const requiredConsent = form.querySelector('input[name="privacy_consent"][required]');
    if (!submitButton || !requiredConsent) return;

    const updateButtonState = () => {
      submitButton.disabled = !(form.checkValidity() && requiredConsent.checked);
    };

    form.addEventListener('input', updateButtonState);
    form.addEventListener('change', updateButtonState);
    form.addEventListener('submit', (event) => {
      updateButtonState();
      if (submitButton.disabled) {
        event.preventDefault();
        form.reportValidity();
      }
    });

    updateButtonState();
  });
});
