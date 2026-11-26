// Injected by Fully Kiosk into every page (Settings > Advanced Web Settings >
// Inject JavaScript; the dashboard installs it). On YouTube TV it reports the
// playing video and position to home-hub every 5 s, so the kitchen card can
// carry on with the same song after the Home button. Other sites: no-op.
(function () {
  if (location.hostname !== 'www.youtube.com' || window.__homeHubHandoff) return;
  window.__homeHubHandoff = true;
  var url = '__HANDOFF_URL__', last = '';
  setInterval(function () {
    var video = document.querySelector('video'), m = location.hash.match(/[?&]v=([\w-]{11})/);
    if (!video || !m || video.paused && last) return;
    var l = location.hash.match(/[?&]list=([\w-]{10,80})/);
    var body = JSON.stringify({ v: m[1], t: Math.floor(video.currentTime || 0), list: l ? l[1] : null });
    if (body === last) return;
    last = body;
    fetch(url, { method: 'POST', body: body, headers: { 'Content-Type': 'text/plain' }, keepalive: true }).catch(function () {});
  }, 5000);
})();

// YouTube TV expects a TV remote, so swipes do nothing on the tablet. Here a swipe
// becomes arrow-key presses (a longer swipe moves further) and taps stay taps. A
// small translucent remote (arrows, OK, Back) sits in the bottom-right corner too.
(function () {
  if (location.hostname !== 'www.youtube.com' || location.pathname.indexOf('/tv') !== 0 || window.__homeHubRemote) return;
  window.__homeHubRemote = true;
  var KEYS = { up: ['ArrowUp', 38], down: ['ArrowDown', 40], left: ['ArrowLeft', 37], right: ['ArrowRight', 39], ok: ['Enter', 13], back: ['Escape', 27] };

  function press(name) {
    var k = KEYS[name], target = document.activeElement;
    if (!target || target.closest('#hh-remote')) target = document.body;
    ['keydown', 'keyup'].forEach(function (type) {
      var e = new KeyboardEvent(type, { key: k[0], code: k[0], bubbles: true, cancelable: true });
      try { Object.defineProperty(e, 'keyCode', { get: function () { return k[1]; } }); Object.defineProperty(e, 'which', { get: function () { return k[1]; } }); } catch (err) {}
      target.dispatchEvent(e);
    });
  }
  function repeat(name, times) {
    for (var i = 0; i < times; i++) { setTimeout(press, i * 90, name); }
  }

  var start = null;
  document.addEventListener('touchstart', function (e) {
    if (e.touches.length !== 1 || e.target.closest && e.target.closest('#hh-remote')) { start = null; return; }
    start = { x: e.touches[0].clientX, y: e.touches[0].clientY };
  }, { passive: true, capture: true });
  document.addEventListener('touchend', function (e) {
    if (!start) return;
    var t = e.changedTouches[0], dx = t.clientX - start.x, dy = t.clientY - start.y, ax = Math.abs(dx), ay = Math.abs(dy);
    start = null;
    if (Math.max(ax, ay) < 40) return; // a tap: leave it alone
    var steps = Math.max(1, Math.min(8, Math.round(Math.max(ax, ay) / 110)));
    // Content follows the finger: swiping up shows what's below.
    repeat(ay > ax ? (dy < 0 ? 'down' : 'up') : (dx < 0 ? 'right' : 'left'), steps);
  }, { passive: true, capture: true });

  function remote() {
    if (document.getElementById('hh-remote') || !document.body) return;
    var css = '#hh-remote{position:fixed;right:18px;bottom:18px;z-index:2147483647;display:grid;grid-template-columns:repeat(3,56px);grid-template-rows:repeat(3,56px);gap:6px;opacity:.55;transition:opacity .2s}'
      + '#hh-remote:active{opacity:.9}#hh-remote button{border:0;border-radius:16px;background:rgba(30,30,30,.75);color:#fff;font:600 22px system-ui,sans-serif;touch-action:manipulation}'
      + '#hh-remote .ok{border-radius:999px;font-size:16px}#hh-remote .back{font-size:14px}';
    var style = document.createElement('style'); style.textContent = css; document.head.appendChild(style);
    var box = document.createElement('div'); box.id = 'hh-remote';
    [['back', '↩'], ['up', '▲'], [''], ['left', '◀'], ['ok', 'OK'], ['right', '▶'], [''], ['down', '▼'], ['']].forEach(function (b) {
      if (!b[0]) { box.appendChild(document.createElement('span')); return; }
      var btn = document.createElement('button'); btn.type = 'button'; btn.className = b[0]; btn.textContent = b[1];
      btn.setAttribute('aria-label', b[0]);
      btn.tabIndex = -1;
      btn.addEventListener('mousedown', function (e) { e.preventDefault(); }); // keep YouTube's focus
      btn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); press(b[0]); });
      box.appendChild(btn);
    });
    document.body.appendChild(box);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', remote); else remote();
  setInterval(remote, 3000); // YouTube TV may rebuild the page
})();
