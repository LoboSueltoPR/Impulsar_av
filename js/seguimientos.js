/* Popup de seguimiento post-contacto*/

function revisarSeguimientosPendientes() {
  if (!window.Auth || !Auth.logueado) return;

  fetch("api/seguimientos_pendientes.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.pendiente) mostrarPopupSeguimiento(data.pendiente);
    })
    .catch(() => {});
}

function mostrarPopupSeguimiento(pendiente) {
  if (document.getElementById("seguimientoPopup")) return;

  const popup = document.createElement("div");
  popup.id = "seguimientoPopup";
  popup.className = "seguimiento-popup";
  popup.innerHTML = `
    <div class="seguimiento-popup-header">
      <img src="images/logo.ico" alt="">
      <span>Nexo de Tierras Argentinas</span>
      <button type="button" class="seguimiento-popup-cerrar" aria-label="Cerrar">&times;</button>
    </div>
    <div class="seguimiento-popup-body">
      <p>${pendiente.pregunta}</p>
      ${
        pendiente.mostrar_link_perfil
          ? `<a href="perfil.html?id=${pendiente.provider_id}" target="_blank" class="seguimiento-popup-link">Ver perfil para calificar</a>`
          : ""
      }
      <div class="seguimiento-popup-opciones">
        ${pendiente.opciones
          .map(
            (op) =>
              `<button type="button" class="seguimiento-popup-btn" data-valor="${op.valor}">${op.texto}</button>`
          )
          .join("")}
      </div>
    </div>
  `;

  document.body.appendChild(popup);

  popup.querySelector(".seguimiento-popup-cerrar").addEventListener("click", () => {
    popup.remove();
  });

  popup.querySelectorAll(".seguimiento-popup-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      popup.querySelectorAll(".seguimiento-popup-btn").forEach((b) => (b.disabled = true));

      fetch("api/seguimiento_responder.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          seguimiento_id: pendiente.seguimiento_id,
          respuesta: btn.dataset.valor
        })
      })
        .then((res) => res.json())
        .then((data) => {
          popup.querySelector(".seguimiento-popup-body").innerHTML = `<p>${data.mensaje}</p>`;
          setTimeout(() => {
            popup.remove();
            revisarSeguimientosPendientes();
          }, 2500);
        })
        .catch(() => {
          popup.remove();
        });
    });
  });
}

document.addEventListener("DOMContentLoaded", () => {
  setTimeout(revisarSeguimientosPendientes, 1200);
});
