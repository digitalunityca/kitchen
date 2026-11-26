<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="no-referrer">
  <title>{{ $answers ? 'Answer key' : 'Homework worksheets' }} · {{ $kid['name'] }}</title>
  <style>
    :root { color-scheme: light; font: 16px/1.4 system-ui, sans-serif; color: #202124; background: #f2f2f7; }
    body { margin: 0; }
    .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin: auto; max-width: 8.5in; padding: 1rem; }
    .toolbar h1 { margin: 0; font-size: 1.3rem; }
    .toolbar p { margin: .25rem 0 0; }
    .toolbar a { color: #005bbb; }
    .toolbar button { border: 0; border-radius: .65rem; background: #006dce; color: white; font: 600 1rem system-ui; padding: .8rem 1.1rem; cursor: pointer; }
    .paper { background: white; color: black; box-sizing: border-box; margin: 0 auto 1rem; max-width: 8.5in; padding: clamp(1rem, 4vw, .55in); }
    .paper h2 { font-size: 1.55rem; margin: 0; }
    .paper header p { margin: .15rem 0 0; }
    .sheet-head { display: flex; align-items: flex-start; gap: 1rem; border-bottom: 1px solid #000; padding-bottom: .6rem; margin-bottom: 1.4rem; }
    .sheet-title { flex: 1; }
    .sheet-meta { text-align: right; font-size: .85rem; }
    .sheet-meta p { margin: 0; }
    .sheet-no { font-weight: 700; font-size: 1.05rem; font-variant-numeric: tabular-nums; }
    .sheet-qr img { width: .9in; height: .9in; display: block; }
    .sums { display: grid; grid-template-columns: repeat(5, 1fr); row-gap: 2.2em; justify-items: center; font-family: ui-monospace, Menlo, monospace; }
    .sum { width: calc(var(--chars) * .62em); text-align: right; line-height: 1.15; }
    .sum span { float: left; }
    .sum-line { border-top: 2px solid #000; margin-top: .1em; font-weight: 700; }
    body.preview .paper { margin-top: 1rem; }
    .exercise { margin-bottom: 1.15rem; overflow-wrap: anywhere; }
    .question { white-space: pre-line; }
    .line { height: .3in; border-bottom: 1px solid #777; }
    @page { size: letter; margin: .55in; }
    @media print {
      :root { font-size: 11pt; line-height: 1.3; background: white; }
      .toolbar { display: none; }
      .paper { margin: 0; padding: 0; max-width: none; }
      .paper + .paper { break-before: page; }
    }
  </style>
</head>
<body @class(['preview' => $preview ?? false])>
  @unless ($preview ?? false)
  <header class="toolbar">
    <div>
      <a href="{{ route('dashboard', absolute: false) }}">← Dashboard</a>
      <h1>{{ $answers ? 'Answer key' : 'Homework worksheets' }} · {{ $kid['name'] }}</h1>
      @if ($tasks === [])
        <p>There is no homework to print.</p>
      @elseif ($needsGeneration ?? false)
        <p role="status" data-packet-status>Generating {{ count($tasks) }} {{ count($tasks) === 1 ? 'worksheet' : 'worksheets' }}…</p>
      @endif
    </div>
    @if ($needsGeneration ?? false)
      <form method="post" action="{{ route('modules.homework.packet.generate', [$kid['id'], $date], absolute: false) }}" data-packet-generate>
        @csrf
        <button type="submit" hidden>Try again</button>
      </form>
    @elseif ($tasks !== [])
      @if ($macPrint ?? false)
        <form method="post" action="{{ route('modules.homework.packet.print', [$kid['id'], $date, '00000000-0000-0000-0000-000000000000'], absolute: false) }}" data-mac-print>
          @csrf
          <button type="submit">Print all worksheets</button>
          <p role="status" data-print-status>Printer: Mac Studio</p>
        </form>
      @else
        <button type="button" data-print>Print worksheet</button>
      @endif
    @endif
  </header>
  @endunless

  @unless ($needsGeneration ?? false)
    @foreach ($tasks as $task)
      @php($isSums = $task['template'] === 'addition' && isset($task['worksheet'][0]['top']))
      @php($layout = $isSums ? \App\Actions\Homework\BuildAdditionSheet::layout($task['worksheet']) : null)
      <article class="paper" lang="fr-CA">
        <header class="sheet-head">
          <div class="sheet-title">
            <h2>{{ $isSums ? $layout['title'] : \App\Modules\Homework\HomeworkModule::TEMPLATES[$task['template']]['sheet'] }}{{ $answers ? ' · CORRIGÉ' : '' }}</h2>
            <p>{{ $isSums ? 'Calcule chaque somme.' : 'Réponds à chaque question.' }}</p>
          </div>
          <div class="sheet-meta">
            <p>{{ $kid['name'] }}</p>
            @isset($task['number'])<p class="sheet-no">{{ \App\Modules\Homework\HomeworkStore::sheetLabel((int) $task['number']) }}</p>@endisset
            <p class="sheet-date">{{ \Carbon\CarbonImmutable::parse($date)->locale('fr_CA')->isoFormat('D MMMM YYYY') }}</p>
          </div>
          @unless ($answers)<div class="sheet-qr">@include('modules.homework.qr', ['task' => $task])</div>@endunless
        </header>
        @if ($isSums)
          <div class="sums" style="--chars: {{ $layout['chars'] }}; font-size: {{ $layout['font'] * 1.25 }}px">
            @foreach ($task['worksheet'] as $p)
              <div class="sum"><div>{{ $p['top'] }}</div><div><span>+</span>{{ $p['bottom'] }}</div><div class="sum-line">{{ $answers ? $p['answer'] : "\u{00A0}" }}</div></div>
            @endforeach
          </div>
        @else
          <ol>
            @foreach ($task['worksheet'] as $exercise)
              <li class="exercise">
                <div class="question">{{ $exercise['question'] }}</div>
                @if ($answers)
                  <p><strong>{{ $exercise['answer'] }}</strong></p>
                @else
                  <div class="line"></div>
                  <div class="line"></div>
                @endif
              </li>
            @endforeach
          </ol>
        @endif
      </article>
    @endforeach
  @endunless

  <script src="{{ asset('js/homework-packet.js') }}"></script>
  <script>
    const print = document.querySelector('[data-print]');
    print?.addEventListener('click', () => {
      if (typeof window.fully?.print === 'function') {
        window.fully.print();
      } else {
        window.print();
      }
    });

    window.HomeworkPacket.bind(document.querySelector('[data-mac-print]'));

    const form = document.querySelector('[data-packet-generate]');
    if (form) {
      const status = document.querySelector('[data-packet-status]');
      const button = form.querySelector('button');
      const pending = status.textContent;
      const generate = async () => {
        button.hidden = true;
        status.textContent = pending;
        try {
          const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
          if (response.status !== 204) {
            throw new Error();
          }
          window.location.reload();
        } catch {
          status.textContent = 'Couldn’t generate the worksheets. Try again.';
          button.hidden = false;
        }
      };
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        generate();
      });
      generate();
    }
  </script>
</body>
</html>
