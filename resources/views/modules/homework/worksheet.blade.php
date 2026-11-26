<!doctype html>
<html lang="fr-CA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $template['sheet'] }} · {{ $kid['name'] }} · {{ $date->toDateString() }}{{ $answers ? ' · Corrigé' : '' }}</title>
<style>
  /* Printable worksheet: US Letter, black and white (Brother DCP-7030 laser), thin lines. */
  @page { size: letter; margin: 12mm 14mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; background: #E9E9EC; color: #000; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
  .page { width: 216mm; min-height: 279mm; margin: 16px auto; background: #fff; padding: 12mm 14mm; box-shadow: 0 4px 24px rgba(0,0,0,.12); }
  .toolbar { text-align: center; margin: 16px 0 0; }
  .toolbar button { font: inherit; font-size: 16px; font-weight: 600; padding: 10px 22px; border-radius: 12px; border: 0; background: #1D1D1F; color: #fff; cursor: pointer; }
  @media print {
    html, body { background: #fff; }
    .page { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
    .toolbar { display: none; }
  }

  header { display: grid; grid-template-columns: 1fr auto; gap: 2mm 8mm; align-items: end; border-bottom: 1.2pt solid #000; padding-bottom: 3mm; margin-bottom: 5mm; }
  header h1 { margin: 0; font-size: 22pt; font-weight: 700; letter-spacing: -.01em; }
  header .points { font-size: 12pt; font-weight: 600; text-align: right; }
  header .meta { grid-column: 1 / -1; display: flex; gap: 8mm; font-size: 11pt; }
  header .meta span { flex: 1; white-space: nowrap; }
  header .meta b { font-weight: 600; }
  header .meta .fill { display: inline-block; min-width: 40mm; border-bottom: .6pt solid #000; }
  .key { display: inline-block; border: 1pt solid #000; padding: 1mm 3mm; font-size: 10pt; font-weight: 700; margin-left: 4mm; vertical-align: middle; }

  section { margin-bottom: 6mm; }
  h2 { font-size: 13pt; margin: 0 0 3mm; font-weight: 700; }
  .grid { display: grid; gap: 4mm 6mm; }
  .item { break-inside: avoid; page-break-inside: avoid; position: relative; }
  .num { position: absolute; top: 0; left: 0; font-size: 8pt; color: #555; }
  .boxed .num { top: 1.5mm; left: 2.5mm; }
  .ans { font-family: "Comic Sans MS", "Chalkboard", cursive; color: #555; font-style: italic; }
  .mono { font-family: "Menlo", "DejaVu Sans Mono", "Courier New", monospace; }

  /* Stacked + − × */
  .column { padding: 1mm 2mm 0 5mm; }
  .column .stack { margin-left: auto; width: max-content; text-align: right; line-height: 1.25; }
  .column .op { float: left; margin-right: 3mm; }
  .column .rule { border-top: 1.2pt solid #000; margin-top: .5mm; }
  .column .answer { height: 11mm; padding-top: 1.5mm; }
  .younger .column .stack { font-size: 22pt; }
  .older .column .stack { font-size: 15pt; }

  /* Long multiplication: room for the partial products. */
  .long .stack { font-size: 16pt; }
  .long .work { height: 26mm; border-bottom: .6pt dashed #999; margin-top: 1mm; }
  .long .answer { height: 10mm; }
  .boxed { border: .6pt solid #999; border-radius: 3mm; padding: 2mm 3mm; }

  /* Long division in the Quebec "potence" layout. */
  .potence { display: grid; grid-template-columns: 1fr 22mm; min-height: 62mm; font-size: 16pt; }
  .potence .dividend { padding: 1mm 3mm 0 0; text-align: right; border-right: 1.2pt solid #000; }
  .potence .divisor { padding: 1mm 0 1mm 3mm; border-bottom: 1.2pt solid #000; align-self: start; }
  .potence .quotient { grid-column: 2; padding: 2mm 0 0 3mm; font-size: 13pt; }

  /* One-line facts */
  .facts { grid-template-columns: repeat(4, 1fr); }
  .fact { font-size: 14pt; padding: 2mm 0; display: flex; gap: 2mm; align-items: baseline; }
  .fact .blank { flex: 1; border-bottom: .6pt solid #000; min-height: 6mm; }

  /* Drawing boxes for groups and sharing (younger kids). */
  .draw-grid { grid-template-columns: 1fr 1fr; gap: 3mm 8mm; }
  .draw .prompt { font-size: 14pt; font-weight: 600; margin-bottom: 1.5mm; }
  .draw .box { height: 21mm; border: .6pt dashed #888; border-radius: 3mm; }
  .draw .eq { font-size: 15pt; margin-top: 2mm; display: flex; gap: 2mm; align-items: baseline; }
  .draw .eq .blank { min-width: 18mm; border-bottom: .8pt solid #000; text-align: center; }

  /* Writing: the model word, then handwriting lines to copy it. */
  .copy { margin-bottom: 4mm; }
  .copy .model { font-size: 24pt; font-family: "Helvetica Neue", Arial, sans-serif; margin: 0 0 1mm 1mm; letter-spacing: .03em; }
  .bands { display: grid; gap: 4mm; }
  .band { position: relative; height: 17mm; }
  .band i { position: absolute; left: 0; right: 0; height: 0; }
  .band .l-top { top: 2mm; border-top: .5pt solid #aaa; }
  .band .l-mid { top: 7.5mm; border-top: .5pt dashed #aaa; }
  .band .l-base { top: 13mm; border-top: 1pt solid #000; }
  footer { margin-top: 4mm; padding-top: 2mm; border-top: .6pt solid #ccc; font-size: 9pt; color: #555; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<p class="toolbar"><button type="button" onclick="window.print()">Imprimer</button></p>
<div class="page {{ $kid['grade'] <= 3 ? 'younger' : 'older' }}">
  <header>
    <h1>{{ $template['sheet'] }} @if ($answers)<span class="key">CORRIGÉ</span>@endif</h1>
    <span class="points">★ {{ $task['points'] }} points</span>
    <div class="meta">
      <span><b>Prénom :</b> {{ $kid['name'] }}</span>
      <span><b>Date :</b> {{ $date->isoFormat('dddd D MMMM YYYY') }}</span>
      <span><b>Parent :</b> <span class="fill">&nbsp;</span></span>
    </div>
  </header>

  @php($n = 0)
  @foreach ($sections as $section)
    <section>
      <h2>{{ $section['heading'] }}</h2>

      @if ($section['kind'] === 'column')
        <div class="grid" style="grid-template-columns: repeat({{ $kid['grade'] <= 3 ? 5 : 4 }}, 1fr)">
          @foreach ($section['items'] as $item)
            <div class="item column mono"><span class="num">{{ ++$n }}</span>
              <div class="stack"><div>{{ $item['top'] }}</div><div><span class="op">{{ $item['op'] }}</span>{{ $item['bottom'] }}</div><div class="rule"></div><div class="answer">@if ($answers)<span class="ans">{{ $item['answer'] }}</span>@endif</div></div>
            </div>
          @endforeach
        </div>

      @elseif ($section['kind'] === 'facts')
        <div class="grid facts">
          @foreach ($section['items'] as $item)
            <div class="item fact"><span>{{ $item['text'] }}</span><span class="blank">@if ($answers)<span class="ans">{{ $item['answer'] }}</span>@endif</span></div>
          @endforeach
        </div>

      @elseif ($section['kind'] === 'long-multiplication')
        <div class="grid" style="grid-template-columns: repeat(3, 1fr)">
          @foreach ($section['items'] as $item)
            <div class="item boxed long column mono"><span class="num">{{ ++$n }}</span>
              <div class="stack"><div>{{ $item['top'] }}</div><div><span class="op">×</span>{{ $item['bottom'] }}</div><div class="rule"></div></div>
              <div class="work"></div>
              <div class="stack"><div class="answer">@if ($answers)<span class="ans">{{ $item['answer'] }}</span>@endif</div></div>
            </div>
          @endforeach
        </div>

      @elseif ($section['kind'] === 'long-division')
        <div class="grid" style="grid-template-columns: repeat(3, 1fr)">
          @foreach ($section['items'] as $item)
            <div class="item boxed mono"><span class="num">{{ ++$n }}</span>
              <div class="potence"><div class="dividend">{{ $item['dividend'] }}</div><div class="divisor">{{ $item['divisor'] }}</div><div class="quotient">@if ($answers)<span class="ans">{{ $item['answer'] }}</span>@endif</div></div>
            </div>
          @endforeach
        </div>

      @elseif ($section['kind'] === 'groups')
        <div class="grid draw-grid">
          @foreach ($section['items'] as $item)
            <div class="item draw">
              <div class="prompt">{{ ++$n }}. {{ $item['groups'] }} groupes de {{ $item['size'] }}</div>
              <div class="box"></div>
              <div class="eq"><span>{{ $item['groups'] }} × {{ $item['size'] }} =</span><span class="blank">@if ($answers)<span class="ans">{{ $item['answer'] }}</span>@endif</span></div>
            </div>
          @endforeach
        </div>

      @elseif ($section['kind'] === 'share')
        <div class="grid draw-grid">
          @foreach ($section['items'] as $item)
            <div class="item draw">
              <div class="prompt">{{ ++$n }}. {{ $item['total'] }} {{ $item['thing'] }} pour {{ $item['people'] }} amis</div>
              <div class="box"></div>
              <div class="eq"><span>Chaque ami reçoit</span><span class="blank">@if ($answers)<span class="ans">{{ $item['answer'] }}</span>@endif</span><span>{{ $item['thing'] }}.</span></div>
            </div>
          @endforeach
        </div>

      @elseif ($section['kind'] === 'copy')
        @foreach ($section['items'] as $item)
          <div class="item copy">
            <p class="model">{{ $item['word'] }}</p>
            <div class="bands" style="grid-template-columns: repeat({{ $item['copies'] }}, 1fr)">
              @for ($c = 0; $c < $item['copies']; $c++)
                <div class="band"><i class="l-top"></i><i class="l-mid"></i><i class="l-base"></i></div>
              @endfor
            </div>
          </div>
        @endforeach
      @endif
    </section>
  @endforeach

  <footer><span>{{ $kid['name'] }} · {{ $kid['grade'] }}e année</span><span>Bravo ! Montre ta feuille à un parent.</span></footer>
</div>
</body>
</html>
