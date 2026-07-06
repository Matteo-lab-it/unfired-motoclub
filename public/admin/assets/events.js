(function () {
  function addRouteStop() {
    const list = document.getElementById("routeStopsList");
    if (!list) return;

    const count = list.querySelectorAll(".route-stop-row").length;
    if (count >= 20) return;

    const row = document.createElement("div");
    row.className = "route-stop-row";
    row.style.cssText = "display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;align-items:start;";
    row.innerHTML = [
      '<label>Localita ' + (count + 1),
      '<input type="text" name="route_stop_title[]" maxlength="120" placeholder="Es. Torino">',
      '</label>',
      '<label>Link Maps nascosto',
      '<input type="text" name="route_stop_description[]" maxlength="255" placeholder="Incolla link Google Maps / Apple Mappe">',
      '</label>'
    ].join("");

    list.appendChild(row);
    const firstInput = row.querySelector("input");
    if (firstInput) firstInput.focus();
  }

  const button = document.getElementById("addRouteStopButton");
  if (button) {
    button.addEventListener("click", addRouteStop);
  }
})();
