{{-- One multiplication in a square grid, one digit per cell, sized from the layout:
     carry row(s), the number, × and the multiplier, then the result row (1-digit
     multiplier) or one row per partial product, a carry row for the sum and the total.
     Shared by the PDF (dompdf) and the on-screen preview. --}}
@php
    $cols = $layout['columns'];
    $cell = $layout['cell'];
    $digitsRow = function (string $value) use ($cols): array {
        $chars = str_split($value);
        return array_merge(array_fill(0, max(0, $cols - count($chars)), ''), $chars);
    };
    $blank = array_fill(0, $cols, '');
    $rows = [];
    for ($i = 0; $i < $layout['carries']; $i++) {
        $rows[] = ['cells' => $blank, 'rule' => false, 'carry' => true];
    }
    $rows[] = ['cells' => $digitsRow((string) $p['top']), 'rule' => false, 'carry' => false];
    $second = $digitsRow((string) $p['bottom']);
    $second[0] = '×';
    $rows[] = ['cells' => $second, 'rule' => true, 'carry' => false];
    if (! $layout['total']) {
        $rows[] = ['cells' => ($answers ?? false) ? $digitsRow($p['answer']) : $blank, 'rule' => false, 'carry' => false];
    } else {
        for ($i = 0; $i < $layout['partials']; $i++) {
            $rows[] = ['cells' => $blank, 'rule' => false, 'carry' => false];
        }
        $rows[] = ['cells' => $blank, 'rule' => true, 'carry' => true];
        $rows[] = ['cells' => ($answers ?? false) ? $digitsRow($p['answer']) : $blank, 'rule' => false, 'carry' => false];
    }
@endphp
<table class="mgrid" role="presentation" style="border-collapse: collapse; margin: 0 auto">
  @foreach ($rows as $row)
    <tr>
      @foreach ($row['cells'] as $c)
        <td style="width: {{ $cell }}pt; height: {{ $cell }}pt; padding: 0; text-align: center; vertical-align: middle; border: .4pt solid {{ $row['carry'] ? '#dcdcdc' : '#b5b5b5' }}; {{ $row['rule'] ? 'border-bottom: 1.6pt solid #000;' : '' }} font-family: DejaVu Sans Mono, ui-monospace, monospace; font-size: {{ round($cell * .62) }}pt; line-height: 1">{{ $c }}</td>
      @endforeach
    </tr>
  @endforeach
</table>
