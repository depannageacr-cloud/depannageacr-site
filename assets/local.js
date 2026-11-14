(() => {
  'use strict';
  const phone = '32471379064';
  const chat = text => 'https://wa.me/' + phone + '?text=' + encodeURIComponent(text);
  const dialog = document.getElementById('locatie-delen');
  const get = document.getElementById('get-location');
  const send = document.getElementById('send-location');
  const status = document.getElementById('location-status');
  let place = '', attempt = 0, opener;
  if (dialog && typeof dialog.showModal === 'function') {
    document.querySelectorAll('[data-open-location]').forEach(link => {
      link.addEventListener('click', event => {
        event.preventDefault();
        opener = link; place = link.dataset.place || ''; attempt++;
        get.disabled = false; get.textContent = 'Mijn locatie ophalen';
        status.textContent = place ? 'Gekozen plaats: ' + place + '. Voeg je exacte locatie of straat toe.' : 'Je kunt ook zonder GPS een adres of foto sturen.';
        send.href = chat('Ik heb pech' + (place ? ' in ' + place : ' in Oost-Vlaanderen') + '. Mijn voertuig en probleem: ');
        send.textContent = 'Open WhatsApp zonder locatie';
        dialog.showModal();
      });
    });
    dialog.addEventListener('close', () => { attempt++; opener?.focus(); });
    get.addEventListener('click', () => {
      if (!window.isSecureContext || !navigator.geolocation) {
        status.textContent = 'Locatie ophalen is hier niet beschikbaar. Open WhatsApp en typ je straat en gemeente of deel daar een kaartpin.';
        return;
      }
      const current = ++attempt;
      get.disabled = true; status.textContent = 'Je toestel vraagt toestemming. Je locatie wordt alleen gebruikt om je WhatsApp-bericht voor te bereiden.';
      navigator.geolocation.getCurrentPosition(position => {
        if (current !== attempt || !dialog.open) return;
        const { latitude, longitude, accuracy } = position.coords;
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180) {
          status.textContent = 'Geen bruikbare locatie ontvangen. Typ je adres in WhatsApp.'; get.disabled = false; return;
        }
        const map = 'https://www.google.com/maps?q=' + latitude.toFixed(6) + ',' + longitude.toFixed(6);
        send.href = chat('Ik heb pech' + (place ? ' in ' + place : ' in Oost-Vlaanderen') + '. Mijn huidige locatie: ' + map + '\nMijn voertuig en probleem: ');
        send.textContent = 'Open WhatsApp met mijn locatie';
        status.textContent = 'Locatie klaar' + (Number.isFinite(accuracy) ? ' (geschatte nauwkeurigheid: ' + Math.round(accuracy) + ' meter)' : '') + '. Controleer de kaartlink in WhatsApp en druk zelf op verzenden.';
        get.disabled = false; get.textContent = 'Locatie opnieuw ophalen'; send.focus();
      }, error => {
        if (current !== attempt || !dialog.open) return;
        const reason = error.code === 1 ? 'Geen toestemming voor je locatie.' : error.code === 3 ? 'Locatie ophalen duurt te lang.' : 'Je toestel kan de locatie niet bepalen.';
        status.textContent = reason + ' Je kunt WhatsApp openen en zelf je straat, gemeente of kaartpin sturen.';
        get.disabled = false;
      }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
  }
  const normal = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  document.querySelectorAll('[data-region-directory]').forEach(box => {
    const input = box.querySelector('input[type="search"]');
    const items = [...box.querySelectorAll('[data-region-item]')];
    const status = box.querySelector('[data-region-status]');
    input.addEventListener('input', () => {
      const query = normal(input.value), parts = query.split(/\s+/).filter(Boolean);
      let count = 0;
      items.forEach(item => { const match = parts.every(part => normal(item.dataset.terms).includes(part)); item.hidden = !match; if (match) count++; });
      box.querySelectorAll('[data-region-group]').forEach(group => { group.hidden = ![...group.querySelectorAll('[data-region-item]')].some(item => !item.hidden); });
      box.querySelector('[data-region-empty]').hidden = count !== 0;
      status.textContent = query ? count + (count === 1 ? ' gemeente gevonden.' : ' gemeenten gevonden.') : 'Alle 55 gemeenten. Kies je plaats voor deelgemeenten en praktische informatie.';
    });
  });
})();
