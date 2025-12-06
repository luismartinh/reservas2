(function () {
  function initSubmitOverlay() {
    var forms = document.querySelectorAll('[data-submit-overlay="true"]');

    forms.forEach(function (form) {
      // evitar bind doble
      if (form.dataset.submitOverlayBound === "1") {
        return;
      }
      form.dataset.submitOverlayBound = "1";

      form.addEventListener("submit", function (e) {
        // botón marcado como overlay-btn
        var btn = form.querySelector("[data-submit-overlay-btn]");
        if (!btn) {
          return;
        }

        // si ya está deshabilitado, no dejar re-enviar
        if (btn.disabled) {
          e.preventDefault();
          return false;
        }

        // deshabilitar botón
        btn.disabled = true;
        btn.classList.add("disabled");

        // guardar HTML original por si algún día querés restaurar
        if (!btn.dataset.originalHtml) {
          btn.dataset.originalHtml = btn.innerHTML;
        }

        // texto "Enviando..."
        var loadingText =
          btn.getAttribute("data-loading-text") || "Enviando...";

        btn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2" ' +
          'role="status" aria-hidden="true"></span>' +
          loadingText;

        // crear backdrop si no existe
        if (!document.getElementById("submit-overlay-backdrop")) {
          var overlayText =
            form.getAttribute("data-overlay-text") ||
            "Procesando, por favor espere...";

          var backdrop = document.createElement("div");
          backdrop.id = "submit-overlay-backdrop";
          backdrop.className = "submit-overlay-backdrop";
          backdrop.innerHTML =
            '<div class="submit-overlay-inner">' +
            '<div class="spinner-border text-light" role="status"></div>' +
            '<div class="mt-3 fw-bold">' +
            overlayText +
            "</div>" +
            "</div>";

          document.body.appendChild(backdrop);
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", initSubmitOverlay);

  // Si usás PJAX en alguna vista donde también quieras esto
  document.addEventListener("pjax:success", initSubmitOverlay);
})();
