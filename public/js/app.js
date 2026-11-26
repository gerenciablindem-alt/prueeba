/* Aportes Simple: menú móvil, tema, paleta de búsqueda (Ctrl K) y asistente de IA. Sin dependencias. */
(function () {
  'use strict';
  var AS = window.AS || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var esc = function (t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };
  var quitar = function (el) { if (el && el.parentNode) el.parentNode.removeChild(el); };

  // Menú móvil
  $$('[data-abrir-menu]').forEach(function (b) { b.addEventListener('click', function () { document.body.classList.add('menu-abierto'); }); });
  $$('[data-cerrar-menu]').forEach(function (b) { b.addEventListener('click', function () { document.body.classList.remove('menu-abierto'); }); });

  // Tema claro / oscuro (recordado en este navegador)
  $$('[data-tema]').forEach(function (b) {
    b.addEventListener('click', function () {
      var html = document.documentElement;
      var oscuroAhora = html.dataset.theme ? html.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
      html.dataset.theme = oscuroAhora ? 'light' : 'dark';
      try { localStorage.setItem('tema', html.dataset.theme); } catch (e) {}
    });
  });

  // ---------- Asistente ----------
  var panel = $('#ia-panel');
  var enPaginaIA = /\/asistente\/?$/.test(location.pathname);
  function abrirIA(pregunta) {
    if (enPaginaIA) {
      var c = $('.chat-card [data-chat]');
      if (c && pregunta) enviar(c, pregunta);
      else if (c && $('textarea', c)) $('textarea', c).focus();
      return;
    }
    panel.hidden = false;
    requestAnimationFrame(function () { panel.classList.add('abierto'); });
    var chat = $('[data-chat]', panel);
    if (pregunta) enviar(chat, pregunta); else { var t = $('textarea', chat); if (t) t.focus(); }
    var log = $('[data-log]', chat); log.scrollTop = log.scrollHeight;
  }
  function cerrarIA() { panel.classList.remove('abierto'); setTimeout(function () { panel.hidden = true; }, 200); }
  $$('[data-abrir-ia]').forEach(function (b) { b.addEventListener('click', function () { abrirIA(); }); });
  $$('[data-cerrar-ia]').forEach(function (b) { b.addEventListener('click', cerrarIA); });

  function burbuja(log, clase, html) {
    quitar($('[data-vacio]', log));
    var d = document.createElement('div'); d.className = 'msg ' + clase; d.innerHTML = html; log.appendChild(d);
    log.scrollTop = log.scrollHeight; return d;
  }
  var nombresHerr = { resumen_general: 'resumen', buscar_afiliados: 'afiliados', listar_afiliaciones: 'radicados', listar_incapacidades: 'incapacidades',
    calcular_incapacidad: 'calculadora', alertas: 'alertas', cartera_ventas: 'cuotas', buscar_ventas: 'ventas' };

  function enviar(chat, pregunta) {
    var log = $('[data-log]', chat), form = $('[data-form]', chat);
    if (!form || chat.dataset.ocupado) return;
    chat.dataset.ocupado = '1';
    burbuja(log, 'yo', esc(pregunta));
    var pensando = burbuja(log, 'ia pensando', '<span class="puntos"><i></i><i></i><i></i></span> Consultando los datos…');
    var btn = $('button[type=submit]', form); btn.disabled = true;
    fetch(AS.ia, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': AS.csrf }, body: JSON.stringify({ pregunta: pregunta }) })
      .then(function (r) { return r.json().catch(function () { return { texto: 'Error ' + r.status + '. Intenta de nuevo.', error: true }; }); })
      .then(function (d) {
        quitar(pensando);
        var usadas = (d.herramientas || []).filter(function (v, i, a) { return a.indexOf(v) === i; }).map(function (h) { return nombresHerr[h] || h; });
        var pie = usadas.length ? '<div class="fuentes">Consultó: ' + usadas.map(esc).join(', ') + '</div>' : '';
        burbuja(log, 'ia' + (d.error ? ' error' : ''), (d.html || esc(d.texto)) + pie);
      })
      .catch(function () { quitar(pensando); burbuja(log, 'ia error', 'No hubo conexión con el servidor. Intenta de nuevo.'); })
      .then(function () { delete chat.dataset.ocupado; btn.disabled = false; var t = $('textarea', form); t.value = ''; t.style.height = ''; t.focus(); });
  }

  $$('[data-chat]').forEach(function (chat) {
    var form = $('[data-form]', chat);
    if (form) {
      var t = $('textarea', form);
      form.addEventListener('submit', function (e) { e.preventDefault(); var q = t.value.trim(); if (q) enviar(chat, q); });
      t.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
      t.addEventListener('input', function () { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight, 160) + 'px'; });
    }
    chat.addEventListener('click', function (e) {
      var s = e.target.closest('[data-sugerencia]'); if (s && form) enviar(chat, s.textContent.trim());
      if (e.target.closest('[data-reiniciar]')) {
        fetch(AS.iaReiniciar, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': AS.csrf } }).then(function () { location.reload(); });
      }
    });
  });

  // ---------- Paleta de comandos ----------
  var dlg = $('#paleta'), q = $('#paleta-q'), res = $('#paleta-res'), sel = 0, items = [], tmr = null, ctl = null;
  function abrirPaleta() { if (!dlg.open) { dlg.showModal(); q.value = ''; pintar(base('')); q.focus(); } }
  function base(texto) {
    var t = texto.toLowerCase();
    var acc = (AS.acciones || []).filter(function (a) { return !t || a.t.toLowerCase().indexOf(t) >= 0; })
      .map(function (a) { return { grupo: 'Acciones', titulo: a.t, url: a.u }; });
    var ia = texto.length > 2 ? [{ grupo: 'Asistente IA', titulo: 'Preguntar: “' + texto + '”', ia: texto }] : [];
    return { ia: ia, acc: acc };
  }
  function pintar(b, datos) {
    items = [].concat(b.ia, datos || [], b.acc);
    sel = 0;
    if (!items.length) { res.innerHTML = '<div class="paleta-vacia">Sin resultados.</div>'; return; }
    var html = '', g = null;
    items.forEach(function (it, i) {
      if (it.grupo !== g) { g = it.grupo; html += '<div class="paleta-grp">' + esc(g) + '</div>'; }
      html += '<a role="option" class="paleta-it' + (i === 0 ? ' sel' : '') + '" data-i="' + i + '" href="' + esc(it.url || '#') + '"><b>' + esc(it.titulo) + '</b>' + (it.detalle ? '<small>' + esc(it.detalle) + '</small>' : '') + '</a>';
    });
    res.innerHTML = html;
  }
  function marcar(n) {
    if (!items.length) return;
    sel = (n + items.length) % items.length;
    $$('.paleta-it', res).forEach(function (el, i) { el.classList.toggle('sel', i === sel); if (i === sel) el.scrollIntoView({ block: 'nearest' }); });
  }
  function ir(i) {
    var it = items[i]; if (!it) return;
    if (it.ia) { dlg.close(); abrirIA(it.ia); } else { location.href = it.url; }
  }
  if (dlg) {
    $$('[data-abrir-paleta]').forEach(function (b) { b.addEventListener('click', abrirPaleta); });
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); abrirPaleta(); }
      if (e.key === 'Escape' && panel && !panel.hidden && !dlg.open) cerrarIA();
    });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    q.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); marcar(sel + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(sel - 1); }
      else if (e.key === 'Enter') { e.preventDefault(); ir(sel); }
    });
    res.addEventListener('click', function (e) { var a = e.target.closest('.paleta-it'); if (a) { e.preventDefault(); ir(+a.dataset.i); } });
    q.addEventListener('input', function () {
      var texto = q.value.trim(), b = base(texto);
      pintar(b);
      clearTimeout(tmr); if (ctl) ctl.abort();
      if (texto.length < 2) return;
      tmr = setTimeout(function () {
        ctl = new AbortController();
        fetch(AS.buscar + '?q=' + encodeURIComponent(texto), { headers: { 'Accept': 'application/json' }, signal: ctl.signal })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (q.value.trim() === texto) pintar(b, d.resultados || []); })
          .catch(function () {});
      }, 180);
    });
  }
})();
