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
