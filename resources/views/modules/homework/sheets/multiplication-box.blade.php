{{-- One multiplication in a square grid: one digit per cell, the × and multiplier under the top number,
     a row per partial product and a total row. Shared by the PDF (dompdf) and the on-screen preview. --}}
@php
    $cols = $layout['columns'];
    $cell = $cell ?? 17;
    $digitsRow = function (string $value) use ($cols): array {
        $chars = str_split($value);
        return array_merge(array_fill(0, max(0, $cols - count($chars)), ''), $chars);
    };
    $rows = [['cells' => $digitsRow((string) $p['top']), 'rule' => false]];
    $second = $digitsRow((string) $p['bottom']);
    $second[0] = '×';
    $rows[] = ['cells' => $second, 'rule' => true];
    $answerRow = $layout['partials'] + ($layout['total'] ? 1 : 0);
    for ($i = 1; $i <= $answerRow; $i++) {
        $isLast = $i === $answerRow;
        $rows[] = [
            'cells' => ($answers ?? false) && $isLast ? $digitsRow($p['answer']) : array_fill(0, $cols, ''),
            'rule' => $layout['total'] && $i === $layout['partials'],
        ];
    }
@endphp
<table class="mgrid" role="presentation" style="border-collapse: collapse; margin: 0 auto">
  @foreach ($rows as $row)
    <tr>
      @foreach ($row['cells'] as $c)
        <td style="width: {{ $cell }}pt; height: {{ $cell }}pt; padding: 0; text-align: center; vertical-align: middle; border: .4pt solid #b5b5b5; {{ $row['rule'] ? 'border-bottom: 1.6pt solid #000;' : '' }} font-family: DejaVu Sans Mono, ui-monospace, monospace; font-size: {{ round($cell * .72) }}pt; line-height: 1">{{ $c }}</td>
      @endforeach
    </tr>
  @endforeach
</table>
