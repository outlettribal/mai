(function () {
  const root = document.querySelector('#mai-client-portal .mai-app');
  if (!root || !window.MaiEsteticasMaiConfig) return;

  root.innerHTML = `
    <div class="mai-card">
      <h4>Agendar cita (4 pasos)</h4>
      <ol>
        <li>Elegir sede</li>
        <li>Elegir servicio</li>
        <li>Elegir fecha/hora</li>
        <li>Especialista o automático</li>
      </ol>
      <button id="mai-load-appointments">Mis citas</button>
      <div id="mai-appointments"></div>
    </div>
  `;

  document.getElementById('mai-load-appointments')?.addEventListener('click', async () => {
    const res = await fetch(`${MaiEsteticasMaiConfig.restUrl}/client/me/appointments`, {
      headers: { 'X-WP-Nonce': MaiEsteticasMaiConfig.nonce }
    });

    const data = await res.json();
    const list = document.getElementById('mai-appointments');
    if (!Array.isArray(data) || data.length === 0) {
      list.innerHTML = '<p>No hay citas aún.</p>';
      return;
    }

    list.innerHTML = data.map((a) => `<div class="mai-chip">#${a.id} · ${a.status} · ${a.start_datetime}</div>`).join('');
  });
})();
