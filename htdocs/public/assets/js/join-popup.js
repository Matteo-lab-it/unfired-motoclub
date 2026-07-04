(function () {
  const dismissedKey = "unfairedJoinPopupDismissedAt";
  const dismissedForMs = 3 * 24 * 60 * 60 * 1000;
  const dismissedAt = Number(localStorage.getItem(dismissedKey) || 0);

  if (dismissedAt && Date.now() - dismissedAt < dismissedForMs) {
    return;
  }

  const isHome = /(^|\/)(index\.html)?$/.test(window.location.pathname);
  const contactHref = isHome ? "#contatti" : "index.html#contatti";

  const popup = document.createElement("aside");
  popup.className = "join-popup";
  popup.setAttribute("role", "dialog");
  popup.setAttribute("aria-modal", "false");
  popup.setAttribute("aria-labelledby", "joinPopupTitle");
  popup.setAttribute("aria-describedby", "joinPopupText");
  popup.innerHTML = `
    <button class="join-popup-close" type="button" aria-label="Chiudi popup">x</button>
    <div>
      <div class="join-popup-kicker">Unisciti a noi</div>
      <div class="join-popup-title" id="joinPopupTitle">Vuoi entrare nel gruppo?</div>
    </div>
    <p class="join-popup-text" id="joinPopupText">Scrivici due righe: ti raccontiamo come partecipare alle prossime uscite.</p>
    <div class="join-popup-actions">
      <a class="join-popup-button" href="${contactHref}">Contattaci</a>
      <a class="join-popup-secondary" href="organizzazione.html">Scopri l'organizzazione</a>
    </div>
  `;

  function dismiss() {
    localStorage.setItem(dismissedKey, String(Date.now()));
    popup.classList.remove("is-visible");
    window.setTimeout(() => popup.remove(), 260);
  }

  const popupDelay = isHome ? 3400 : 1200;

  window.setTimeout(() => {
    document.body.appendChild(popup);
    popup.querySelector(".join-popup-close").addEventListener("click", dismiss);
    popup.querySelector(".join-popup-button").addEventListener("click", dismiss);
    popup.addEventListener("keydown", event => {
      if (event.key === "Escape") {
        dismiss();
      }
    });
    requestAnimationFrame(() => popup.classList.add("is-visible"));
  }, popupDelay);
})();
