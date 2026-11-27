<!doctype html>
<html lang="fr-CA">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 38pt 40pt; }
    /* Helvetica (as printed so far); PdfText wraps characters it lacks (→, ≠…) in DejaVu Sans. */
    body { font-family: Helvetica, sans-serif; font-size: 11pt; line-height: 1.3; color: black; }
    article + article { page-break-before: always; }
    table.head { width: 100%; border-collapse: collapse; }
    table.head td { vertical-align: top; padding: 0; }
    .title { margin: 0 0 3pt; font-size: 15pt; font-weight: bold; }
    .inst { margin: 0; font-size: 11pt; }
    .meta { text-align: right; width: 210pt; }
    .meta-line { font-size: 12pt; margin: 0 0 6pt; }
    .meta-small { font-size: 9pt; color: #333; margin: 0; }
    .no { font-size: 13pt; font-weight: bold; letter-spacing: .5pt; }
    .qr { width: 64pt; height: 64pt; }
    .rule { border-top: 1pt solid black; margin: 10pt 0 22pt; }
    /* Column additions: right-aligned in a monospace font so place values line up. */
    table.sums { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.sums td { text-align: center; vertical-align: top; }
    .prob { display: inline-block; font-family: DejaVu Sans Mono, monospace; text-align: right; line-height: 1.15; }
    .prob .line { border-top: 1.5pt solid black; margin-top: 3pt; }
    ol { padding-left: 25pt; margin: 0; }
    li { margin-bottom: 12pt; page-break-inside: avoid; overflow-wrap: break-word; }
    .write { height: 20pt; border-bottom: .5pt solid #888; }
  </style>
</head>
<body>
  @foreach ($tasks as $task)
    @php($isSums = $task['template'] === 'addition' && isset($task['worksheet'][0]['top']))
    @php($isGrid = $task['template'] === 'multiplication' && isset($task['worksheet'][0]['top']))
    @php($isDiv = $task['template'] === 'division' && isset($task['worksheet'][0]['kind']))
    @php($layout = $isSums ? \App\Actions\Homework\BuildAdditionSheet::layout($task['worksheet']) : ($isGrid ? \App\Actions\Homework\BuildMultiplicationSheet::layout($task['worksheet']) : ($isDiv ? \App\Actions\Homework\BuildDivisionSheet::layout($task['worksheet']) : null)))
    @php($qr = \App\Modules\Homework\WorksheetQrCode::make($kid['id'], $date, $task))
    <article>
      <table class="head" role="presentation">
        <tr>
          <td>
            <p class="title">{{ $layout ? $layout['title'] : \App\Modules\Homework\HomeworkModule::TEMPLATES[$task['template']]['sheet'] }}</p>
            <p class="inst">{{ $isDiv ? $layout['instruction'] : ($isSums ? 'Calcule chaque somme.' : ($isGrid ? 'Calcule chaque produit. Un chiffre par case.' : (isset($task['worksheet'][0]['word']) ? 'Recopie chaque mot dans la case à côté.' : 'Réponds à chaque question.'))) }}</p>
          </td>
          <td class="meta">
            <table role="presentation" style="width: 100%; border-collapse: collapse">
              <tr>
                <td style="text-align: right; vertical-align: top; padding-right: 8pt">
                  <p class="meta-line">Nom : ____________</p>
                  @isset($task['number'])<p class="meta-small"><span class="no">{{ \App\Modules\Homework\HomeworkStore::sheetLabel((int) $task['number']) }}</span></p>@endisset
                  <p class="meta-small">{{ \Carbon\CarbonImmutable::parse($date)->locale('fr_CA')->isoFormat('D MMMM YYYY') }}</p>
                </td>
                <td style="width: 64pt; vertical-align: top"><img class="qr" src="{{ $qr['image'] }}" alt=""></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      <div class="rule"></div>

      @if ($isSums)
        {{-- As many sums as fit at handwriting size (BuildAdditionSheet::layout). --}}
        <table class="sums" role="presentation">
          @foreach (array_chunk($task['worksheet'], $layout['columns']) as $row)
            <tr>
              @foreach ($row as $p)
                <td style="width: {{ round(100 / $layout['columns'], 3) }}%; padding-bottom: {{ $loop->parent->last ? 0 : $layout['gapY'] }}pt">@include('modules.homework.sheets.addition-box', ['p' => $p, 'layout' => $layout, 'answers' => false])</td>
              @endforeach
              @for ($i = count($row); $i < $layout['columns']; $i++)<td style="width: {{ round(100 / $layout['columns'], 3) }}%"></td>@endfor
            </tr>
          @endforeach
        </table>
      @elseif ($isGrid)
        <table class="sums" role="presentation">
          @foreach (array_chunk($task['worksheet'], $layout['across']) as $row)
            <tr>
              @foreach ($row as $p)
                <td style="width: {{ round(100 / $layout['across'], 3) }}%; padding-bottom: {{ $loop->parent->last ? 0 : $layout['gap'] }}pt">@include('modules.homework.sheets.multiplication-box', ['p' => $p, 'layout' => $layout, 'answers' => false])</td>
              @endforeach
              @for ($i = count($row); $i < $layout['across']; $i++)<td style="width: {{ round(100 / $layout['across'], 3) }}%"></td>@endfor
            </tr>
          @endforeach
        </table>
      @elseif ($isDiv)
        {{-- Divisions made on the hub: as many as fit (BuildDivisionSheet::layout). --}}
        <table class="sums" role="presentation">
          @foreach (array_chunk($task['worksheet'], $layout['across']) as $row)
            <tr>
              @foreach ($row as $p)
                <td style="width: {{ round(100 / $layout['across'], 3) }}%; padding-bottom: {{ $loop->parent->last ? 0 : $layout['gapY'] }}pt">@include('modules.homework.sheets.division-box', ['p' => $p, 'layout' => $layout, 'answers' => false])</td>
              @endforeach
              @for ($i = count($row); $i < $layout['across']; $i++)<td style="width: {{ round(100 / $layout['across'], 3) }}%"></td>@endfor
            </tr>
          @endforeach
        </table>
      @elseif ($task['template'] === 'writing' && isset($task['worksheet'][0]['word']))
        {{-- Écriture: Mot | Écris | Mot | Écris, each writing space with a baseline and a dashed midline (GenerateWritingWords::layout). --}}
        @php($w = \App\Actions\Homework\GenerateWritingWords::layout())
        <table class="copy" role="presentation" style="width: 100%; border-collapse: collapse; table-layout: fixed">
          <tr>
            @for ($i = 0; $i < $w['pairs']; $i++)
              <td style="width: {{ $w['word'] }}pt; height: {{ $w['header'] }}pt; font-size: 9pt; color: #555; vertical-align: bottom; {{ $i > 0 ? 'padding-left: 10pt;' : '' }}">Mot</td>
              <td style="height: {{ $w['header'] }}pt; font-size: 9pt; color: #555; vertical-align: bottom; padding-left: 6pt">Écris</td>
            @endfor
          </tr>
          @foreach (array_chunk($task['worksheet'], $w['pairs']) as $pair)
            <tr>
              @for ($i = 0; $i < $w['pairs']; $i++)
                <td style="height: {{ $w['row'] }}pt; vertical-align: bottom; padding: 0 0 3pt {{ $i > 0 ? '10pt' : '0' }}; font-size: 17pt; border-bottom: .8pt solid #555">{!! isset($pair[$i]) ? \App\Support\PdfText::html($pair[$i]['question']) : '' !!}</td>
                <td style="height: {{ $w['row'] }}pt; padding: 0 0 0 6pt; vertical-align: bottom; border-bottom: .8pt solid #555; border-left: .4pt solid #ccc">
                  <div style="height: {{ round($w['row'] / 2, 2) }}pt; border-bottom: .5pt dashed #aaa; margin-bottom: {{ round($w['row'] / 2 - 4, 2) }}pt"></div>
                </td>
              @endfor
            </tr>
          @endforeach
        </table>
      @else
        <ol>
          @foreach ($task['worksheet'] as $exercise)
            <li>
              {!! \App\Support\PdfText::html($exercise['question']) !!}
              <div class="write"></div>
              <div class="write"></div>
            </li>
          @endforeach
        </ol>
      @endif
    </article>
  @endforeach
</body>
</html>
