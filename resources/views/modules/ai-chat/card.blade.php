@php($inHeader = $module->placement() === 'header')
@if ($configured)
<button type="button" class="aic {{ $inHeader ? 'aic-pill' : 'card' }}" id="aic-open" aria-label="Ask ChatGPT by voice"
  data-session="{{ route('modules.ai-chat.session') }}" data-csrf="{{ csrf_token() }}">
@else
<div class="aic aic-off {{ $inHeader ? 'aic-pill' : 'card' }}" aria-label="Ask ChatGPT (not set up)">
@endif
  <span class="aic-icon {{ $inHeader ? '' : 'tile-icon' }}" aria-hidden="true">
    <svg width="{{ $inHeader ? 22 : 32 }}" height="{{ $inHeader ? 22 : 32 }}" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.5V21"/></svg>
  </span>
  @if ($inHeader)
    <span class="aic-pill-text"><b>Ask ChatGPT</b>@unless ($configured)<small>Add OPENAI_API_KEY to turn this on</small>@endunless</span>
  @else
  <div class="row" style="align-items:flex-end">
    <div>
      <span class="tile-title">Ask ChatGPT</span>
      <span class="tile-sub">{{ $configured ? 'Tap and talk: recipes, swaps, timings' : 'Add OPENAI_API_KEY to turn this on' }}</span>
    </div>
  </div>
  @endif
@if ($configured)
</button>

<div class="aic-panel" id="aic-panel" hidden role="dialog" aria-modal="true" aria-label="Voice chat">
  <button type="button" class="aic-close" id="aic-close" aria-label="Close voice chat">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
  </button>
  <p class="aic-status" id="aic-status">Connecting…</p>
  <button type="button" class="aic-orb" id="aic-orb" aria-label="Mute or unmute the microphone"><span></span></button>
  <div class="aic-captions" aria-live="polite">
    <p class="aic-you" id="aic-you"></p>
    <p class="aic-bot" id="aic-bot"></p>
  </div>
  <p class="aic-hint" id="aic-hint">Just talk. Interrupt any time. Tap the orb to mute.</p>
  <audio id="aic-audio" autoplay playsinline></audio>
</div>
@else
</div>
@endif

@once
@push('styles')
<style>
  .aic { text-align: left; }
  .aic.card { width: 100%; }
  .aic-icon { background: linear-gradient(135deg, #0071E3, #8E5CF7); }
  .aic-pill { display: flex; align-items: center; gap: 12px; padding: 7px 22px 7px 7px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow); transition: background-color .6s ease, transform .15s ease; }
  .aic-pill:active { transform: scale(.97); }
  .aic-pill .aic-icon { width: 44px; height: 44px; border-radius: 999px; display: flex; align-items: center; justify-content: center; }
  .aic-pill-text { display: flex; flex-direction: column; line-height: 1.2; }
  .aic-pill-text b { font-size: 17px; font-weight: 600; letter-spacing: -.01em; }
  .aic-pill-text small { font-size: 12px; color: var(--sub); }
  .aic-off .aic-icon { background: var(--sub); }
  .aic-off.card { opacity: .75; }
  .aic-panel {
    position: fixed; inset: 0; z-index: 50; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 36px;
    padding: 48px 64px; background: rgba(0, 0, 0, .86); -webkit-backdrop-filter: blur(24px); backdrop-filter: blur(24px); color: #F5F5F7;
    animation: aic-in .25s ease-out;
  }
  .aic-panel[hidden] { display: none; }
  @keyframes aic-in { from { opacity: 0; transform: scale(1.02); } to { opacity: 1; transform: none; } }
  .aic-close { position: absolute; top: 32px; right: 36px; width: 64px; height: 64px; border-radius: 999px; background: rgba(255,255,255,.12); color: #fff; display: flex; align-items: center; justify-content: center; }
  .aic-status { margin: 0; font-size: 22px; font-weight: 500; letter-spacing: .01em; color: #A1A1A6; min-height: 28px; }
  .aic-orb { width: 220px; height: 220px; border-radius: 999px; position: relative; display: flex; align-items: center; justify-content: center; }
  .aic-orb span {
    width: 180px; height: 180px; border-radius: 999px; display: block; transform: scale(var(--level, 1)); transition: transform .08s linear, filter .3s;
    background: radial-gradient(circle at 35% 30%, #8FD3FF 0%, #0A84FF 35%, #6E4BF2 75%, #3A1C9E 100%);
    box-shadow: 0 0 80px rgba(10, 132, 255, .55), 0 0 160px rgba(110, 75, 242, .35);
  }
  .aic-panel.speaking .aic-orb span { background: radial-gradient(circle at 35% 30%, #FFE0F0 0%, #FF6FB5 30%, #8E5CF7 75%, #3A1C9E 100%); box-shadow: 0 0 90px rgba(255, 111, 181, .5); }
  .aic-panel.thinking .aic-orb span { animation: aic-breathe 1.2s ease-in-out infinite; }
  .aic-panel.muted .aic-orb span, .aic-panel.idle .aic-orb span { filter: grayscale(1) brightness(.7); }
  @keyframes aic-breathe { 50% { transform: scale(.9); } }
  .aic-captions { width: min(900px, 100%); min-height: 150px; display: flex; flex-direction: column; gap: 14px; text-align: center; }
  .aic-captions p { margin: 0; overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; }
  .aic-you { font-size: 20px; color: #A1A1A6; -webkit-line-clamp: 2; }
  .aic-bot { font-size: 30px; font-weight: 500; line-height: 1.3; letter-spacing: -.01em; -webkit-line-clamp: 4; }
  .aic-hint { margin: 0; font-size: 15px; color: #6E6E73; }
</style>
@endpush
@endonce

@if ($configured)
@push('scripts')
<script>
// Voice chat with OpenAI's Realtime API over WebRTC. The server hands out a
// one-minute client secret (POST data-session); audio goes straight between the
// tablet and OpenAI. Music pauses while the panel is open (dashboard:audio-claim).
(function () {
  var tile = document.getElementById('aic-open'), panel = document.getElementById('aic-panel');
  var $ = function (id) { return document.getElementById(id); };
  var QUIET_MS = 2 * 60 * 1000, CALLS_URL = 'https://api.openai.com/v1/realtime/calls';
  var pc = null, dc = null, mic = null, ctx = null, meters = [], raf = 0, quietTimer = 0, lastActivity = 0, open = false, userItem = null;

  function status(text, state) {
    $('aic-status').textContent = text;
    ['listening', 'thinking', 'speaking', 'muted', 'idle'].forEach(function (s) { panel.classList.toggle(s, s === state); });
  }
  function activity() { lastActivity = Date.now(); }
  function fail(text) { status(text, 'idle'); $('aic-hint').textContent = 'Close and try again.'; teardown(); }

  function meter(stream) {
    try {
      var a = ctx.createAnalyser(); a.fftSize = 256;
      ctx.createMediaStreamSource(stream).connect(a);
      meters.push({ a: a, buf: new Uint8Array(a.frequencyBinCount) });
    } catch (e) {}
  }
  function animate() {
    var level = 0;
    meters.forEach(function (m) {
      m.a.getByteTimeDomainData(m.buf);
      var peak = 0;
      for (var i = 0; i < m.buf.length; i++) peak = Math.max(peak, Math.abs(m.buf[i] - 128));
      level = Math.max(level, peak / 128);
    });
    $('aic-orb').style.setProperty('--level', (1 + Math.min(level * 1.6, 0.35)).toFixed(3));
    raf = requestAnimationFrame(animate);
  }

  function onEvent(e) {
    var ev; try { ev = JSON.parse(e.data); } catch (err) { return; }
    activity();
    switch (ev.type) {
      case 'input_audio_buffer.speech_started':
        status('Listening…', 'listening'); break;
      case 'input_audio_buffer.speech_stopped':
        status('Thinking…', 'thinking'); break;
      case 'conversation.item.input_audio_transcription.delta':
        if (userItem !== ev.item_id) { userItem = ev.item_id; $('aic-you').textContent = ''; }
        $('aic-you').textContent += ev.delta || ''; break;
      case 'conversation.item.input_audio_transcription.completed':
        userItem = ev.item_id; $('aic-you').textContent = ev.transcript || ''; break;
      case 'response.created':
        $('aic-bot').textContent = ''; break;
      case 'response.output_audio_transcript.delta':
      case 'response.audio_transcript.delta':
        status('Speaking…', 'speaking'); $('aic-bot').textContent += ev.delta || ''; break;
      case 'output_audio_buffer.stopped':
      case 'response.done':
        if (!panel.classList.contains('muted')) status('Listening…', 'listening'); break;
      case 'error':
        $('aic-hint').textContent = (ev.error && ev.error.message) || 'Something went wrong.'; break;
    }
  }

  async function start() {
    open = true; activity();
    $('aic-you').textContent = ''; $('aic-bot').textContent = '';
    $('aic-hint').textContent = 'Just talk. Interrupt any time. Tap the orb to mute.';
    panel.hidden = false; status('Connecting…', 'thinking');
    document.dispatchEvent(new CustomEvent('dashboard:audio-claim', { detail: { by: 'ai-chat' } }));
    try { ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { ctx = null; }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.RTCPeerConnection) {
      return fail(location.protocol === 'https:' ? 'This browser can’t do voice chat.' : 'Voice chat needs the https address.');
    }
    try {
      mic = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } });
    } catch (e) {
      return fail(e && e.name === 'NotAllowedError'
        ? 'The microphone is blocked. Allow it in Fully: Web Content Settings › Enable Microphone Access.'
        : 'No microphone found.');
    }
    if (!open) return teardown();

    var secret;
    try {
      var r = await fetch(tile.dataset.session, { method: 'POST', headers: { 'X-CSRF-TOKEN': tile.dataset.csrf, 'Accept': 'application/json' } });
      var body = await r.json().catch(function () { return {}; });
      if (!r.ok) {
        return fail(body.error === 'not_configured' ? 'Add OPENAI_API_KEY to the hub’s .env to turn this on.'
          : r.status === 429 ? 'Too many tries. Wait a minute.' : 'Can’t reach ChatGPT right now.');
      }
      secret = body.value;
    } catch (e) { return fail('Can’t reach the hub.'); }
    if (!open) return teardown();

    try {
      pc = new RTCPeerConnection();
      pc.ontrack = function (e) { var audio = $('aic-audio'); audio.srcObject = e.streams[0]; audio.play().catch(function () {}); if (ctx) meter(e.streams[0]); };
      pc.onconnectionstatechange = function () { if (open && (pc.connectionState === 'failed' || pc.connectionState === 'disconnected')) fail('The connection dropped.'); };
      pc.addTrack(mic.getTracks()[0], mic);
      dc = pc.createDataChannel('oai-events');
      dc.addEventListener('message', onEvent);
      dc.addEventListener('open', function () {
        status('Listening…', 'listening');
        dc.send(JSON.stringify({ type: 'response.create', response: { instructions: 'Greet the cook in one short, warm sentence and ask what they are making.' } }));
      });
      var offer = await pc.createOffer();
      await pc.setLocalDescription(offer);
      var answer = await fetch(CALLS_URL, { method: 'POST', body: offer.sdp, headers: { 'Authorization': 'Bearer ' + secret, 'Content-Type': 'application/sdp' } });
      if (!answer.ok) return fail('ChatGPT refused the call (' + answer.status + ').');
      await pc.setRemoteDescription({ type: 'answer', sdp: await answer.text() });
    } catch (e) { return fail('Couldn’t start the voice chat.'); }

    if (ctx) { if (ctx.state === 'suspended') ctx.resume().catch(function () {}); meter(mic); animate(); }
    quietTimer = setInterval(function () {
      if (Date.now() - lastActivity > QUIET_MS) close();
    }, 5000);
  }

  function teardown() {
    clearInterval(quietTimer); cancelAnimationFrame(raf);
    if (dc) try { dc.close(); } catch (e) {}
    if (pc) try { pc.close(); } catch (e) {}
    if (mic) mic.getTracks().forEach(function (t) { t.stop(); });
    if (ctx) ctx.close().catch(function () {});
    $('aic-audio').srcObject = null;
    pc = dc = mic = ctx = null; meters = []; userItem = null;
  }
  function close() {
    if (!open) return;
    open = false; teardown(); panel.hidden = true; panel.className = 'aic-panel';
    document.dispatchEvent(new CustomEvent('dashboard:audio-release', { detail: { by: 'ai-chat' } }));
  }

  tile.addEventListener('click', function () { if (!open) start(); });
  $('aic-close').addEventListener('click', close);
  $('aic-orb').addEventListener('click', function () {
    if (!mic) return;
    var track = mic.getTracks()[0]; track.enabled = !track.enabled; activity();
    if (track.enabled) status('Listening…', 'listening'); else status('Muted. Tap the orb to talk.', 'muted');
  });
  document.addEventListener('visibilitychange', function () { if (document.hidden) close(); });
})();
</script>
@endpush
@endif
