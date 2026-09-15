/* Menú hamburguesa */
 
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("hamburgerBtn");
  const panel = document.getElementById("navbarPanel");
  if (!btn || !panel) return;
 
  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    const abierto = panel.classList.toggle("active");
    btn.setAttribute("aria-expanded", abierto);
    btn.innerHTML = abierto
      ? '<i class="fas fa-times"></i>'
      : '<i class="fas fa-bars"></i>';
  });
 
  document.addEventListener("click", (e) => {
    const clickAdentro = panel.contains(e.target) || btn.contains(e.target);
    if (!clickAdentro && panel.classList.contains("active")) {
      panel.classList.remove("active");
      btn.setAttribute("aria-expanded", "false");
      btn.innerHTML = '<i class="fas fa-bars"></i>';
    }
  });
});