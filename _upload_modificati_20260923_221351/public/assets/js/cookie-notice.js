(function () {
  const storageKey = "unfairedCookieNotice";
  let banner = document.getElementById("cookieBanner");

  if (!banner) {
    banner = document.createElement("div");
    banner.className = "cookie-banner";
    banner.id = "cookieBanner";
    banner.setAttribute("role", "dialog");
    banner.setAttribute("aria-live", "polite");
    banner.setAttribute("aria-labelledby", "cookieTitle");
    banner.setAttribute("aria-describedby", "cookieText");
    banner.innerHTML = `
      <div><strong id="cookieTitle">Cookie e strumenti tecnici</strong><p id="cookieText">Usiamo solo strumenti tecnici necessari al funzionamento del sito e alla memoria di questa scelta. Non usiamo cookie di profilazione o marketing.</p></div>
      <div class="cookie-actions">
        <a class="cookie-link" href="cookie-policy.html">Leggi policy</a>
        <button class="cookie-button" type="button" id="acceptCookies">Ho capito</button>
      </div>
    `;
    document.body.appendChild(banner);
  }

  const acceptCookies = document.getElementById("acceptCookies");
  const cookiePreferences = document.getElementById("cookiePreferences");

  function showCookieBanner() {
    banner.classList.add("is-visible");
  }

  function hideCookieBanner() {
    banner.classList.remove("is-visible");
    localStorage.setItem(storageKey, "accepted");
  }

  if (!localStorage.getItem(storageKey)) {
    showCookieBanner();
  }

  if (acceptCookies) {
    acceptCookies.addEventListener("click", hideCookieBanner);
  }

  if (cookiePreferences) {
    cookiePreferences.addEventListener("click", showCookieBanner);
  }
})();
