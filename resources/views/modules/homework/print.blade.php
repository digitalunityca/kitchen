<!doctype html>
<html lang="fr-CA">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 38pt 40pt; }
    body { font: 11pt/1.3 DejaVu Sans, sans-serif; color: black; }
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
    @php($layout = $isSums ? \App\Actions\Homework\BuildAdditionSheet::layout($task['worksheet']) : ($isGrid ? \App\Actions\Homework\BuildMultiplicationSheet::layout($task['worksheet']) : null))
    @php($qr = \App\Modules\Homework\WorksheetQrCode::make($kid['id'], $date, $task))
    <article>
      <table class="head" role="presentation">
        <tr>
          <td>
            <p class="title">{{ $layout ? $layout['title'] : \App\Modules\Homework\HomeworkModule::TEMPLATES[$task['template']]['sheet'] }}</p>
            <p class="inst">{{ $isSums ? 'Calcule chaque somme.' : ($isGrid ? 'Calcule chaque produit. Un chiffre par case.' : 'Réponds à chaque question.') }}</p>
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
        <table class="sums" role="presentation">
          @foreach (array_chunk($task['worksheet'], \App\Actions\Homework\BuildAdditionSheet::COLUMNS) as $row)
            <tr>
              @foreach ($row as $p)
                <td style="padding-bottom: {{ $layout['rows'] >= 4 ? 30 : 46 }}pt">
                  <div class="prob" style="font-size: {{ $layout['font'] }}pt; width: {{ round($layout['chars'] * 0.62, 2) }}em">
                    <div>{{ $p['top'] }}</div>
                    <div><span style="float: left">+</span>{{ $p['bottom'] }}</div>
                    <div class="line">&nbsp;</div>
                  </div>
                </td>
              @endforeach
            </tr>
          @endforeach
        </table>
      @elseif ($isGrid)
        <table class="sums" role="presentation">
          @foreach (array_chunk($task['worksheet'], \App\Actions\Homework\BuildMultiplicationSheet::ACROSS) as $row)
            <tr>
              @foreach ($row as $p)
                <td style="padding-bottom: 26pt">@include('modules.homework.sheets.multiplication-box', ['p' => $p, 'layout' => $layout, 'answers' => false, 'cell' => 17])</td>
              @endforeach
            </tr>
          @endforeach
        </table>
      @else
        <ol>
          @foreach ($task['worksheet'] as $exercise)
            <li>
              {{ $exercise['question'] }}
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
