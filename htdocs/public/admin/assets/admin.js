document.addEventListener("change", event => {
  const target = event.target;
  if (!(target instanceof HTMLSelectElement) || !target.dataset.autosubmit) {
    return;
  }

  target.form?.submit();
});

document.addEventListener("submit", event => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  const message = form.dataset.confirm;
  if (message && !window.confirm(message)) {
    event.preventDefault();
  }
});
