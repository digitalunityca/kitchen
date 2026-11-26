<!doctype html>
<html lang="fr-CA">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 40pt; }
    body { font: 11pt/1.3 DejaVu Sans, sans-serif; color: black; }
    article + article { page-break-before: always; }
    h1 { margin: 0 0 3pt; font-size: 19pt; }
    p { margin: 0 0 4pt; }
    ol { padding-left: 25pt; }
    li { margin-bottom: 12pt; page-break-inside: avoid; overflow-wrap: break-word; }
    .line { height: 20pt; border-bottom: .5pt solid #888; }
    table { margin-top: 10pt; width: 100%; border-top: .75pt solid #777; page-break-inside: avoid; }
    td { padding: 8pt 10pt 0 0; vertical-align: middle; }
    img { width: 95pt; height: 95pt; }
  </style>
</head>
<body>
  @foreach ($tasks as $task)
    <article>
      <h1>{{ \App\Modules\Homework\HomeworkModule::TEMPLATES[$task['template']]['sheet'] }}</h1>
      <p>{{ $kid['name'] }} · {{ \Carbon\CarbonImmutable::parse($date)->locale('fr_CA')->isoFormat('D MMMM YYYY') }}</p>
      <ol>
        @foreach ($task['worksheet'] as $exercise)
          <li>
            {{ $exercise['question'] }}
            <div class="line"></div>
            <div class="line"></div>
          </li>
        @endforeach
      </ol>
      @php($qr = \App\Modules\Homework\WorksheetQrCode::make($kid['id'], $date, $task))
      <table role="presentation">
        <tr>
          <td style="width: 95pt"><img src="{{ $qr['image'] }}" alt="Code QR de confirmation"></td>
          <td><strong>Vérification par un parent</strong><br>Scannez le code QR et entrez votre NIP pour confirmer ce devoir.</td>
        </tr>
      </table>
    </article>
  @endforeach
</body>
</html>
