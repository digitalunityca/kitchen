# Sample worksheets (27 Nov 2026, second set)

One sheet per homework type, as the printer gets it: the same `print.blade.php` → Dompdf PDF on Letter paper, rendered at 192 dpi (2×). Each sheet is at the level the hub picks for that kid now, copied from the live progress.

Arthur's school grade is 2e, but his working grade is 3e, so his sheets follow 3e. File names use the working grade.

They were made on a local test copy, not the live database:
- the number is a sample;
- "Nom" is left blank;
- the QR code points to example.invalid, with placeholder kid ids.

The AI writes the texts for division, grammar and reading, and the Écriture words; the names in those texts are invented.

**Layouts:** additions and multiplications fill one page, sized for handwriting: 7 mm per digit and 9.5 mm per row with a carry row; square 8 mm grid cells with rows for the carries, the partial products, the sum's carries and the total. Écriture is a 4-column table (Mot | Écris | Mot | Écris) with a baseline and a dashed midline. Divisions are made on the hub: table facts as "a ÷ b = ▢" lines, written divisions in the crochet layout. Every sheet fits on one page (there is a test for it).

| File | Kid / grade | Level | What it practises |
|---|---|---|---|
| addition-5e-L4.png | Theo, 5e | 4 (upkeep) | Column addition, 4 digits + 4 digits, 15 per page |
| addition-3e-L1.png | Arthur, 3e (school 2e) | 1 (this month, →) | Column addition, 3 digits + 2 digits, 20 per page |
| multiplication-5e-L2.png | Theo, 5e | 2 (this month, →) | 3 digits × 2 digits, 9 grids with partial-product and total rows |
| multiplication-3e-L1.png | Arthur, 3e | 1 (this month, →) | Multiplication tables × 2 to × 5, 30 grids |
| division-5e-L1.png | Theo, 5e | 1 (upkeep until January) | Division facts ÷ 2, ÷ 5, ÷ 10 as "a ÷ b = ▢" lines, 30 per page, made on the hub |
| division-5e-L2-crochet.png | Theo's grade, 5e (3 digits ÷ 1 digit, MATH.OPS.A.7a level 2) | 2 | Long division in the Québec crochet layout: 8 mm squares, 2 rows per quotient digit, 6 per page |
| division-3e-L1.png | Arthur, 3e | 1 (this month, →) | Division facts ÷ 2, ÷ 5, ÷ 10, 30 per page |
| grammar-5e-L1.png | Theo, 5e | 1 (this month, ★) | Subject–verb agreement, pronoun subject |
| grammar-3e-L1.png | Arthur, 3e | 1 (this month, ★) | Silent letters (lettres muettes) |
| reading-5e-L0.png | Theo, 5e | – | Short texts with questions (no levels yet) |
| reading-3e-L0.png | Arthur, 3e | – | Short text with questions (no levels yet) |
| writing-3e-L0.png | Arthur, 3e | – | Écriture: 30 AI words for 3e in 4 columns, Mot \| Écris \| Mot \| Écris |

No level adds 3 numbers yet. The builder supports it (`addends: 3`), and the tests cover its layout.
