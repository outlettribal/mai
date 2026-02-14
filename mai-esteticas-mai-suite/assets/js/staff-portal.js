(function () {
  const root = document.querySelector('#mai-staff-portal .mai-app');
  if (!root) return;

  root.innerHTML = `
    <div class="mai-card">
      <h4>Agenda operativa</h4>
      <p>Desde aquí el staff puede aprobar, reprogramar, cancelar o finalizar citas usando los endpoints REST.</p>
      <p>Estados: Pendiente → Procesando pago → Agendado → Finalizado.</p>
    </div>
  `;
})();
