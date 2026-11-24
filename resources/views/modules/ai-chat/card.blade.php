{{--
  "Ask ChatGPT": a blue mic button floating bottom right. Tap to talk, tap again
  to end. The button itself shows the conversation: a spinner while connecting,
  rings that follow your voice while you speak, a glow that follows ChatGPT's
  voice while it answers. The latest caption appears in a bubble above it.
--}}
<div class="aic" id="aic" data-state="idle"
  @if ($configured) data-session="{{ route('modules.ai-chat.session') }}" @if ($familySafe) data-moderate="{{ route('modules.ai-chat.moderate') }}" @endif data-weather="{{ route('modules.ai-chat.weather') }}" data-search="{{ route('modules.ai-chat.search') }}" data-csrf="{{ csrf_token() }}" @endif>
  <p class="aic-bubble" id="aic-bubble" role="status" aria-live="polite"></p>
  <div class="aic-stage">
    <span class="aic-ring" aria-hidden="true"></span>
    <span class="aic-ring aic-ring-2" aria-hidden="true"></span>
    <span class="aic-spin" aria-hidden="true"></span>
    <button type="button" class="aic-fab {{ $configured ? '' : 'aic-off' }}" id="aic-fab"
      aria-label="{{ $configured ? 'Talk to ChatGPT' : 'Ask ChatGPT (not set up)' }}" aria-pressed="false">
      <svg class="aic-mic" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="2.5" width="6" height="12" rx="3" fill="#fff"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.5V21"/></svg>
    </button>
  </div>
  @if ($configured)<audio id="aic-audio" autoplay playsinline></audio>@endif
</div>

@push('styles')
<style>
  .aic { position: relative; --lvl: 0; }
  .aic-stage { position: relative; width: 64px; height: 64px; }
  .aic-fab {
    position: relative; z-index: 1; width: 64px; height: 64px; border-radius: 999px; display: flex; align-items: center; justify-content: center;
    background: var(--accent); box-shadow: 0 4px 14px rgba(0, 113, 227, .35), 0 10px 28px rgba(0, 0, 0, .12);
    transition: background .3s ease, transform .15s ease, box-shadow .3s ease;
  }
  .aic-fab:active { transform: scale(.94); }
  .aic-fab.aic-off { opacity: .6; box-shadow: 0 6px 18px rgba(0, 0, 0, .12); }
  body.night .aic-fab { box-shadow: 0 0 0 1px rgba(255,255,255,.08), 0 8px 26px rgba(10, 132, 255, .35); }
  .aic-mic { position: relative; z-index: 2; }

  /* Rings and spinner sit behind the glyph, hidden until a state needs them. */
  .aic-ring, .aic-spin { position: absolute; inset: 0; border-radius: 999px; pointer-events: none; opacity: 0; }
  .aic-ring { background: var(--accent); z-index: 0; }
  .aic-spin { inset: -7px; border: 3px solid transparent; border-top-color: var(--accent); border-right-color: var(--accent); z-index: 2; }

  /* Connecting / thinking: spinner around the button. */
  .aic[data-state="connecting"] .aic-spin, .aic[data-state="thinking"] .aic-spin { opacity: 1; animation: aic-spin .9s linear infinite; }

  /* Listening: two rings that grow with your voice. */
  .aic[data-state="listening"] .aic-ring { opacity: calc(.18 + var(--lvl) * .4); transform: scale(calc(1.12 + var(--lvl) * .9)); transition: transform .08s linear, opacity .08s linear; }
  .aic[data-state="listening"] .aic-ring-2 { opacity: calc(.1 + var(--lvl) * .25); transform: scale(calc(1.3 + var(--lvl) * 1.5)); }

  /* Speaking: purple-blue glow that breathes with ChatGPT's voice, plus soft waves. */
  .aic[data-state="speaking"] .aic-fab {
    background: linear-gradient(135deg, #0A84FF, #8E5CF7);
    box-shadow: 0 0 calc(18px + var(--lvl) * 46px) calc(var(--lvl) * 14px) rgba(142, 92, 247, .55);
  }
  .aic[data-state="speaking"] .aic-ring { background: #8E5CF7; animation: aic-wave 1.6s ease-out infinite; }
  .aic[data-state="speaking"] .aic-ring-2 { animation-delay: .8s; }

  @keyframes aic-spin { to { transform: rotate(360deg); } }
  @keyframes aic-wave { from { opacity: .45; transform: scale(1); } to { opacity: 0; transform: scale(2.1); } }

  .aic-bubble {
    position: absolute; right: 44px; bottom: 74px; margin: 0; width: max-content; max-width: 440px; padding: 12px 16px;
    border-radius: 18px 18px 4px 18px; background: var(--card); color: var(--text); box-shadow: var(--shadow), 0 6px 20px rgba(0,0,0,.08);
    font-size: 17px; line-height: 1.35; opacity: 0; transform: translateY(6px); transition: opacity .35s ease, transform .35s ease; pointer-events: none;
    overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 4;
  }
  .aic-bubble.show { opacity: 1; transform: none; }
  .aic-bubble small { display: block; font-size: 12px; font-weight: 600; color: var(--sub); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
  body.night .aic-bubble { background: #2C2C2E; }

  @media (prefers-reduced-motion: reduce) {
    .aic-fab, .aic-bubble, .aic-ring { transition: none; }
    .aic-ring, .aic-ring-2 { animation: none !important; transform: none !important; }
    .aic[data-state="listening"] .aic-ring { opacity: .25; transform: scale(1.18) !important; }
    .aic[data-state="speaking"] .aic-fab { box-shadow: 0 0 24px rgba(142, 92, 247, .55); }
    .aic[data-state="connecting"] .aic-spin, .aic[data-state="thinking"] .aic-spin { animation: none; opacity: .6; border-color: var(--accent); }
  }
</style>
@endpush

@push('scripts')
<script>
@if ($configured)
// Voice chat with OpenAI's Realtime API over WebRTC. The hub hands out a
// one-minute client secret (data-session); audio goes straight between the
// tablet and OpenAI. Music pauses while talking (dashboard:audio-claim/release).
(function () {
  var root = document.getElementById('aic'), fab = document.getElementById('aic-fab'), bubble = document.getElementById('aic-bubble');
  var QUIET_MS = 2 * 60 * 1000, BUBBLE_MS = 5000, CALLS_URL = 'https://api.openai.com/v1/realtime/calls';
  var pc = null, dc = null, mic = null, ctx = null, micMeter = null, outMeter = null, raf = 0, quietTimer = 0, bubbleTimer = 0;
  var active = false, lastActivity = 0, level = 0, userItem = null, botText = '';

  function state(s) { root.dataset.state = s; fab.setAttribute('aria-pressed', s === 'idle' ? 'false' : 'true'); fab.setAttribute('aria-label', s === 'idle' ? 'Talk to ChatGPT' : 'End the chat'); }
  function say(text, who, sticky) {
    bubble.innerHTML = '';
    if (who) { var w = document.createElement('small'); w.textContent = who; bubble.appendChild(w); }
    bubble.appendChild(document.createTextNode(text));
    bubble.classList.add('show');
    clearTimeout(bubbleTimer);
    if (!sticky) bubbleTimer = setTimeout(function () { bubble.classList.remove('show'); }, BUBBLE_MS);
  }
  function activity() { lastActivity = Date.now(); }

  function meter(stream) {
    try {
      var a = ctx.createAnalyser(); a.fftSize = 256; ctx.createMediaStreamSource(stream).connect(a);
      return { a: a, buf: new Uint8Array(a.fftSize) };
    } catch (e) { return null; }
  }
  function peak(m) {
    if (!m) return 0;
    m.a.getByteTimeDomainData(m.buf);
    var p = 0; for (var i = 0; i < m.buf.length; i++) p = Math.max(p, Math.abs(m.buf[i] - 128));
    return Math.min(1, (p / 128) * 2.2);
  }
  function animate() {
    var s = root.dataset.state, target = s === 'listening' ? peak(micMeter) : s === 'speaking' ? peak(outMeter) : 0;
    level += (target - level) * (target > level ? 0.5 : 0.12);
    root.style.setProperty('--lvl', level.toFixed(3));
    raf = requestAnimationFrame(animate);
  }

  function onEvent(e) {
    var ev; try { ev = JSON.parse(e.data); } catch (err) { return; }
    activity();
    switch (ev.type) {
      case 'input_audio_buffer.speech_started': state('listening'); break;
      case 'input_audio_buffer.speech_stopped': state('thinking'); break;
      case 'conversation.item.input_audio_transcription.completed':
        if (ev.transcript && root.dataset.state !== 'speaking') say(ev.transcript, 'You');
        moderate(ev.transcript); break;
      case 'response.created': botText = ''; moderatedUpTo = 0; break;
      case 'output_audio_buffer.started': state('speaking'); break;
      case 'response.output_audio_transcript.delta':
      case 'response.audio_transcript.delta':
        state('speaking'); botText += ev.delta || ''; say(botText, 'ChatGPT', true); moderateAnswerSoFar(false); break;
      case 'response.output_audio_transcript.done':
      case 'response.audio_transcript.done':
        if (botText) { say(botText, 'ChatGPT'); moderateAnswerSoFar(true); } break;
      case 'output_audio_buffer.stopped':
      case 'output_audio_buffer.cleared':
        if (active) state('listening'); break;
      case 'response.function_call_arguments.done':
        runTool(ev); break;
      case 'error':
        if (ev.error && ev.error.message) say(ev.error.message); break;
    }
  }

  function fail(text) { say(text); end(); }

  // Family-safe mode, second line of defence after the persona rules: every
  // line (yours and ChatGPT's) goes through OpenAI moderation via the hub. If
  // one is flagged, the answer stops at once and the chat ends.
  /** The assistant called a tool (get_weather, search_web): answer it from the hub, then let it speak. */
  function runTool(ev) {
    var args = {}; try { args = JSON.parse(ev.arguments || '{}'); } catch (e) {}
    var request;
    if (ev.name === 'get_weather') {
      request = fetch(root.dataset.weather + (args.day ? '?day=' + encodeURIComponent(args.day) : ''), { headers: { 'Accept': 'application/json' } });
    } else if (ev.name === 'search_web') {
      say('Searching the web…', 'ChatGPT', true);
      request = fetch(root.dataset.search, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': root.dataset.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: String(args.query || '').slice(0, 300) })
      });
    } else {
      return;
    }
    state('thinking'); activity();
    request
      .then(function (r) { return r.ok ? r.json() : { ok: false, available: false, note: 'The tool failed. Say you could not look it up right now.' }; })
      .catch(function () { return { ok: false, available: false, note: 'The tool failed. Say you could not look it up right now.' }; })
      .then(function (result) {
        if (!active) return;
        activity();
        send({ type: 'conversation.item.create', item: { type: 'function_call_output', call_id: ev.call_id, output: JSON.stringify(result) } });
        send({ type: 'response.create' });
      });
  }

  var moderatedUpTo = 0;
  function send(event) { if (dc && dc.readyState === 'open') dc.send(JSON.stringify(event)); }
  function moderate(text) {
    if (!text || !root.dataset.moderate) return;
    fetch(root.dataset.moderate, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': root.dataset.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: text })
    }).then(function (r) { return r.ok ? r.json() : null; }).then(function (result) {
      if (!result || !result.flagged || !active) return;
      send({ type: 'response.cancel' });
      send({ type: 'output_audio_buffer.clear' });
      var audio = document.getElementById('aic-audio'); if (audio) audio.muted = true;
      say('Let’s talk about something else.');
      end();
      if (audio) audio.muted = false;
    }).catch(function () {});
  }
  /** Check ChatGPT's answer a sentence at a time while it streams, so a bad line is cut short. */
  function moderateAnswerSoFar(final) {
    var upTo = final ? botText.length : Math.max(botText.lastIndexOf('. '), botText.lastIndexOf('? '), botText.lastIndexOf('! ')) + 1;
    if (upTo > moderatedUpTo) { moderate(botText.slice(moderatedUpTo, upTo)); moderatedUpTo = upTo; }
  }

  async function start() {
    active = true; activity(); state('connecting');
    document.dispatchEvent(new CustomEvent('dashboard:audio-claim', { detail: { by: 'ai-chat' } }));
    try { ctx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { ctx = null; }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.RTCPeerConnection) {
      return fail(location.protocol === 'https:' ? 'This browser can’t do voice chat.' : 'Voice chat needs the https address.');
    }
    try {
      mic = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } });
    } catch (e) {
      return fail(e && e.name === 'NotAllowedError' ? 'The microphone is blocked. Allow it in Fully: Web Content Settings › Enable Microphone Access.' : 'No microphone found.');
    }
    if (!active) return end();

    var secret;
    try {
      var r = await fetch(root.dataset.session, { method: 'POST', headers: { 'X-CSRF-TOKEN': root.dataset.csrf, 'Accept': 'application/json' } });
      var body = await r.json().catch(function () { return {}; });
      if (!r.ok) {
        return fail(body.error === 'not_configured' ? 'Add OPENAI_API_KEY to the hub’s .env to turn this on.'
          : r.status === 429 ? 'Too many tries. Wait a minute.' : 'Can’t reach ChatGPT right now.');
      }
      secret = body.value;
    } catch (e) { return fail('Can’t reach the hub.'); }
    if (!active) return end();

    try {
      pc = new RTCPeerConnection();
      pc.ontrack = function (e) {
        var audio = document.getElementById('aic-audio'); audio.srcObject = e.streams[0]; audio.play().catch(function () {});
        if (ctx) outMeter = meter(e.streams[0]);
      };
      pc.onconnectionstatechange = function () { if (active && pc && (pc.connectionState === 'failed' || pc.connectionState === 'disconnected')) fail('The connection dropped.'); };
      pc.addTrack(mic.getTracks()[0], mic);
      dc = pc.createDataChannel('oai-events');
      dc.addEventListener('message', onEvent);
      dc.addEventListener('open', function () {
        state('listening');
        dc.send(JSON.stringify({ type: 'response.create', response: { instructions: 'Greet the cook in one short, warm sentence and ask what they are making.' } }));
      });
      var offer = await pc.createOffer();
      await pc.setLocalDescription(offer);
      var answer = await fetch(CALLS_URL, { method: 'POST', body: offer.sdp, headers: { 'Authorization': 'Bearer ' + secret, 'Content-Type': 'application/sdp' } });
      if (!answer.ok) return fail('ChatGPT refused the call (' + answer.status + ').');
      if (!active) return end();
      await pc.setRemoteDescription({ type: 'answer', sdp: await answer.text() });
    } catch (e) { return fail('Couldn’t start the voice chat.'); }

    if (ctx) { if (ctx.state === 'suspended') ctx.resume().catch(function () {}); micMeter = meter(mic); }
    cancelAnimationFrame(raf); animate();
    quietTimer = setInterval(function () { if (Date.now() - lastActivity > QUIET_MS) { say('Ended after 2 minutes of quiet.'); end(); } }, 5000);
  }

  function end() {
    var wasActive = active;
    active = false; clearInterval(quietTimer); cancelAnimationFrame(raf);
    if (dc) try { dc.close(); } catch (e) {}
    if (pc) try { pc.close(); } catch (e) {}
    if (mic) mic.getTracks().forEach(function (t) { t.stop(); });
    if (ctx) ctx.close().catch(function () {});
    var audio = document.getElementById('aic-audio'); if (audio) audio.srcObject = null;
    pc = dc = mic = ctx = micMeter = outMeter = null; userItem = null; level = 0;
    root.style.setProperty('--lvl', '0'); state('idle');
    if (wasActive) document.dispatchEvent(new CustomEvent('dashboard:audio-release', { detail: { by: 'ai-chat' } }));
  }

  fab.addEventListener('click', function () { if (active) end(); else start(); });
  document.addEventListener('visibilitychange', function () { if (document.hidden && active) end(); });
})();
@else
(function () {
  var fab = document.getElementById('aic-fab'), bubble = document.getElementById('aic-bubble'), t = 0;
  fab.addEventListener('click', function () {
    bubble.textContent = 'Add OPENAI_API_KEY to the hub’s .env to turn on Ask ChatGPT.';
    bubble.classList.add('show'); clearTimeout(t); t = setTimeout(function () { bubble.classList.remove('show'); }, 4000);
  });
})();
@endif
</script>
@endpush
