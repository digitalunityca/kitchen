# Task interface review (27 Nov 2026)

How I looked: on a local test copy at the tablet's CSS size (1280 × 800), with test PINs, kids renamed to their grade ("5e année", "2e année") and no photos. I tested sheets and chores in every state, scored approvals, a reward, the print error and the parent pages. No code was changed.

The screenshots in this folder use test data only.

**What works well**
- The white, Apple-like style is consistent: cards, one accent colour per kid, rounded buttons.
- Tap targets are all at least 48 px; the keypad buttons are 72 px.
- The French reads naturally.
- Errors are clear (e.g. "L'imprimante n'est pas joignable… Demande à un parent").
- The score dialog (Bien +15 / Quelques erreurs +15 / À revoir +8) is easy to understand.
- History's sheet numbers match the paper.

## Prioritized findings (problem → proposed fix)

Quick wins are marked ⚡.

1. ⚡ **Theo's next multiplication sheet steps back to "Tables × 2 à × 5".**
   - He was already doing 3 digits × 2 digits.
   - Cause: tables mastery is ★ in 5e, so it comes before → skills, and its starting level was seeded at 1.
   - Fix: start ★ skills that were → last year at their top level when the kid already practised harder levels of the same sheet type. Or prefer the → skill when the ★ skill is a prerequisite the kid already exceeds.
2. ⚡ **Devoirs tiles: the level line is too long and is cut off under the "−" button** ("Niveau 4 · En colonnes : 4 chiffres + 4 chif…", "1 imprimée(s) aujou…"). It is also adult jargon. *03-devoirs-levels.jpg*
   - Fix: a short kid label on its own line ("4 chiffres + 4 chiffres").
   - Move "−" below the card, outside the text.
   - Write "1 imprimée aujourd'hui" instead of "(s)".
   - Make the subtitles 16–17 px (now 14 px).
3. ⚡ **The 🔁 mark on Multiplication and Division has no explanation for a 7–11 year old.**
   - Fix: a small orange "À revoir" chip with words instead of the emoji.
4. ⚡ **Printed sheets** (see docs/samples):
   - The AI numbers its own questions, so they print as "1. 1)".
   - The 5e division sheet repeats the focus text inside the questions and includes 56 ÷ 8, which is outside ÷ 2/5/10.
   - "→" prints as "?" (the PDF font has no glyph for it).
   - The title says "1 chiffres".
   - Fix: strip leading "1)" / "1." from AI text; tell the AI to keep to the focus without quoting it and to check the numbers; replace → with a glyph the font has; fix the plural.
5. **Rewards: before homework is checked, every card is faded and the same sentence repeats 6 times.** The screen feels dead, and full orange bars ("enough points") look locked. *07-recompenses.jpg*
   - Fix: one banner at the top ("Fais vérifier tes devoirs pour ouvrir les récompenses de ce soir"); cards at normal contrast with a small lock and the cost; no repeated line.
6. **Kid home: the numbers are confusing, and space is wasted.** *02-kid-home.jpg*
   - "0 sur 2 vérifiées aujourd'hui" counts sheets only, while "3 tâches attendent" counts sheets and chores.
   - The points show twice (73 big, ★ 73 at the top).
   - The tall Devoirs tile is mostly empty.
   - Fix:
     - one line: "Aujourd'hui : 2 feuilles à faire vérifier, 1 tâche";
     - drop the ★ 73 pill on home;
     - show today's sheets as small icons on the Devoirs tile;
     - make Nouvelles smaller until it has content.
7. ⚡ **A celebration replays when the page reloads, right over the PIN keypad.** *01-pin-celebration.jpg*
   - Fix: on the first load, set the events cursor without celebrating; celebrate only new approvals.
8. **Aider à la maison: "J'ai fini" can't be undone, and the green borders on every card are loud.** *05-aider-a-la-maison.jpg*
   - Fix:
     - after "J'ai fini", a 5-second "Annuler" in the thank-you toast;
     - plain white cards with the "J'ai fini" button as the only accent;
     - waiting and done chores move to the bottom.
9. **History is a kid screen with parent controls.** *06-historique.jpg*
   - Every row to check has two parent buttons, plus a third (Aperçu) on sheets.
   - The week line counts sheets only ("3 feuilles, +28 points" while chores earned +5).
   - "Pas faite" doesn't fit masculine items.
   - Fix:
     - one "Vérifier" button per row, opening the parent dialog with Pas fait / Bien / Quelques erreurs / À revoir inside;
     - week line "3 feuilles, 1 tâche, +33 points";
     - "Pas fait" for all items.
10. **Parent pages are long.** *08-progres.jpg, 09-settings-chores.jpg*
    - Progrès › "Ce mois-ci" shows about 25 chips.
    - The chores settings page has 7 forms, each with its own Enregistrer button, and the emoji boxes look disabled.
    - Fix:
      - Progrès: show the skills on the sheets first, with "Tout le programme du mois" folded;
      - Chores: a compact list (emoji, name, points, on/off) that opens one row to edit.

Suggested order: the quick wins 1, 2, 3, 4 and 7 in one pass; then 5, 6 and 8 together, since they touch the same components; then 9 and 10.

## Update: kid home (finding 6) and themes, 27 Nov 2026

Done, with the kids' themes:
- **Points:** shown once (the big number); the pill only appears on the other screens.
- **Aujourd'hui:** one block with sheets checked, a progress bar and the chores done.
- **Devoirs tile:** today's sheets as small icons, checked ones in green.
- **Nouvelles:** a slim bar instead of a full tile.

**Themes** are stored per kid (`kids.theme`, seeded from the config): **soccer** for Theo, **gaming and building bricks** for Arthur.
- Both are original drawings: pitch, net, ball and a generic striped #10 jersey; generic controllers and studded bricks.
- No logos, brands or real people; nothing downloaded.
- The theme sits soft behind translucent white cards on the home screen, and fainter on the PIN screen and the other screens.

Screenshots (test names "Élève 5e" / "Élève 3e", no photos):
- 11-kid-home-soccer.jpg
- 12-kid-pin-soccer.jpg
- 13-kid-home-gaming.jpg
- 14-kid-pin-gaming.jpg

## Update: more realistic backgrounds, 27 Nov 2026

Both themes were redrawn as original, realistic illustrations (nothing downloaded, no logos or real people):
- **Soccer:** an evening stadium out of focus with floodlights, a mown pitch in perspective, a goal with its net, and a shaded ball.
- **Gaming:** a desk in front of a glowing screen, a shaded controller, and 3D studded bricks.

Screenshots (test names, no photos):
- 15-kid-pin-soccer-realistic.jpg
- 16-kid-home-soccer-realistic.jpg
- 17-kid-home-gaming-realistic.jpg
