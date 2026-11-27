# Learning plan: curriculum-driven exercises and household tasks

Status: steps 1–5 built and live on 27 Nov 2026, with Oleg's answers: full points for Bien and Quelques erreurs, half for À revoir; the chore list as proposed; month windows from our estimates. See docs/PROJECT.md › Curriculum and levels. Step 6 is still to come.

Goal: the worksheets follow what each kid learns at school this year (Theo, 5e; Arthur, 2e), adapt to how the last sheets went, and don't repeat themselves. Household help ("Aider à la maison") earns points the same way homework does.

---

## 1. Sources checked

### Quebec "Progression des apprentissages" (PDA), official

| Subject | Document |
|---|---|
| Mathématique, primaire (2009) | [PDA math (PDF)](https://cdn-contenu.quebec.ca/cdn-contenu/education/pfeq/primaire/progressions-apprentissages/PFEQ-progression-apprentissages-mathematique-primaire.pdf) |
| Français, langue d'enseignement (2009, oral 2011) | [PDA français (PDF)](https://cdn-contenu.quebec.ca/cdn-contenu/education/pfeq/primaire/progressions-apprentissages/PFEQ-progression-apprentissages-francais-langue-enseignement-primaire.pdf) |

**What the PDA gives, for each item and each school year (1re to 6e):**
- → the student learns it with the teacher;
- ★ the student does it alone by the end of the year;
- shaded: the student reuses it.

**How items are organized:**
- Math: field → section → letter → number, e.g. *Arithmétique › Opérations sur des nombres › A. Nombres naturels › 4.b "processus conventionnels, somme de deux nombres naturels ayant au plus 4 chiffres"*.
- French: Lexique, Orthographe d'usage, Grammaire de la phrase, Conjugaison, and the reading, writing and oral competencies.

**What it doesn't give:**
- **Codes:** none. We make stable ones from the numbering, e.g. `MATH.ARI.OPS.A.4b`.
- **Months or étapes:** the PDA is per year only. The year has 3 étapes (report cards around 20 Nov, 15 Mar and end of June), but each school plans its own order. So month windows are **our estimate**, which the parent can edit and which the real school homework corrects.
- **Machine-readable data:** the → ★ marks are graphics in the PDF and are lost in text extraction. The items needed for 2e and 5e have to be encoded by hand.

**Coming change:** a new French program (*programme actualisé*) becomes mandatory in **2027-2028**. It brings a **prescribed spelling list per grade** (liste orthographique), which is ideal for the writing sheets' word lists. The 2009 PDA still applies for 2026-27.

### Marble Skill Taxonomy (github.com/withmarbleapp/os-taxonomy)

- **Format:** JSON in `data/`:
  - `topics.json` (1,912 topics). Each has `id`, `subject`, `domain`, `name`, `description`, mastery `evidence`, `ageRangeStart/End` and linked `standards`.
  - `dependencies.json`: prerequisite edges `topicId → prerequisiteId`, `hard`/`soft`, with a reason.
  - Pinned commit checked: `acac33d`.
- **Math coverage fits:**
  - 583 math topics: 312 overlap ages 7–8 (Arthur), 386 overlap ages 10–11 (Theo).
  - Domains: place value, addition & subtraction, multiplication & division, fractions, decimals, measurement, geometry, data.
- **Limits:**
  - **English only**, and **no Quebec alignment** (the closest is Ontario Math 2020, about 1,100 links).
  - Nothing for French as a language. Its "English" subject doesn't transfer.
- **License:** data **ODbL 1.0**, text content **CC BY-SA 4.0**. Private use is free with attribution. The home-hub repo is **public**, so vendoring a derived file would make that file ODbL (share-alike) with an attribution notice. That's acceptable, but it's an obligation.
- **Recommendation:** don't make it a dependency and don't vendor it at first.
  - Use it as a **reference while encoding** our Quebec skills, mainly to suggest math prerequisite links (record the Marble id in `source_ref`).
  - Vendor a pinned subset later, only if we want the graph in the app: `database/data/marble/` with math ages 7–11, about 400 topics plus their edges, `LICENSE-ODbL` and `PROVENANCE`.

---

## 2. Data model

The kids already have a `kids` table and the data is in MySQL. Each change below is a migration that carries the existing rows over, tested on a copy of the real data first (as for the MySQL move).

**Curriculum**
- `skills`:
  - `id`, `subject` (`math` | `francais`), `domain` (e.g. *Opérations*, *Fractions*, *Orthographe d'usage*), `code` (unique, e.g. `MATH.ARI.OPS.A.4b`), `name_fr`, `description_fr`;
  - `source` (`pda` | `custom`) and `source_ref` (PDA page/item; optional Marble id), `active`.
- `skill_prerequisites`: `skill_id`, `prerequisite_id`, `strength` (`hard` | `soft`). Unique pair; a cycle is rejected when saved (tested).
- `skill_grades` (one skill spans several years):
  - `skill_id`, `grade`, `expectation` (`apprend` → | `maitrise` ★ | `reinvestit`);
  - `window_start_month` and `window_end_month` (9 = Sept … 6 = June): our estimate, editable.

**Exercises**
- `exercise_types`: today's `TEMPLATES` constant becomes rows.
  - `slug` (addition, multiplication, division, reading, writing…), `name_fr`, `emoji`, `subject`, `points`;
  - `generator` (`addition_columns`, `multiplication_grid`, `ai_packet`, …): which builder makes the sheet. Today's addition grid and 3×3 multiplication grid stay as they are.
  - `layout`, `grades_from` / `grades_to`, `active`.
- `exercise_variants`: one exercise type practising one skill at one level.
  - `exercise_type_id`, `skill_id`, `level` (1–5), `params` JSON.
  - Examples: addition `{digits:[2,2], carry:false}` → `{digits:[4,3], carry:true}`; multiplication `{tables:[2,5,10]}` → `{top_digits:3, bottom_digits:2}`.
  - The builders read `params` instead of grade constants.

**Tasks**
- `task_types`: `slug` (`homework`, `home_help`; later `school`), `name_fr`, `needs_approval`.
- `tasks`: `homework_tasks` renamed and generalized.
  - Existing columns, plus `task_type_id`, `exercise_variant_id` (nullable), `chore_id` (nullable), `points` (now a column), `score` (nullable: `bien` | `erreurs` | `revoir`), `done_at`, `approved_at`.
  - The `data` JSON keeps the generated sheet as today.
  - Migration: every current row becomes `task_type = homework`, mapped from `template` to `exercise_type`.
- `homework_ledger` gains `task_ref` (FK to `tasks.id`, nullable). The old `task_id` string ("t1") stays during the change.

**Progress**
- `kid_skill_progress`:
  - unique (`kid_id`, `skill_id`);
  - `level`, `status` (`nouveau` | `en_cours` | `maitrise` | `a_revoir`);
  - `attempts`, `bien_count`, `last_score`, `last_seen_at`, `next_review_at`.

**Home help**
- `chores`: `slug`, `name_fr`, `emoji`, `points`, `per_day` (default 1), `days` (school days / every day / weekend), `active`.
- `chore_kid` pivot: which kid sees which chore.

---

## 3. Progression rules

**Today, the parent can only approve or reject.** History or the QR page asks for the parent PIN, then either:
- approve: full points;
- "Pas fait": back to *à faire*.

There's no way to say "done, but with mistakes".

**Proposal:** three buttons on approval (History and the QR page), same PIN:

| Button | Points (to confirm, Q1) | Progress |
|---|---|---|
| ✅ **Bien** | 100 % | +1 good result; 2 in a row at a level → next level |
| 🟠 **Quelques erreurs** | 100 % | stays at this level |
| 🔁 **À revoir** | 50 % | level −1; at level 1 → next sheet practises the skill's *hard* prerequisite |

"Pas fait" stays as it is.

**Choosing the exercise.** The kid still taps the tiles as today. The hub picks the **variant** (skill + level) behind each tile, in this order:
1. **Due review**: `next_review_at` has passed, or the last result was "À revoir".
2. **This month's window**: skills whose `skill_grades` window covers the current month, not yet mastered. Lowest level first; ★ (`maitrise`) skills before → (`apprend`) skills.
3. **Maintenance**: a mastered skill, oldest `last_seen_at` first.
4. **July–August**: maintenance and review only.

**Variety rules**
- No two sheets of the same exercise type in a row on the same day: the tile shows ★ *Recommandé* on another type.
- No same variant within **3 school days**, unless it's a review.
- Each sheet also keeps its own random numbers, as today.

**Mastery**
- A skill counts as mastered once it reaches its top level for the kid's grade with 2 × **Bien**.
- Reviews follow at +7 days, then +21. A "Bien" on a review keeps it mastered; "À revoir" sends it back to `a_revoir`.

**Stuck warning:** two "À revoir" in a row on the same skill → a note in History for the parent ("Arthur bloque sur *retenue en addition*").

**AI sheets** (reading, word problems): the prompt gets the skill's `name_fr` and the level's description instead of only the topic name. They aren't graded automatically; the parent's score is the signal.

---

## 4. Home help ("Aider à la maison")

**Kid area:** a fifth tile, *Aider à la maison*.
- It shows the kid's chores for today, each with its emoji and points.
- The kid taps **J'ai fini** → *à vérifier*.
- The parent approves in History (same list, same PIN; no score for chores) → points go to the ledger.

**Seed list** (editable):

| Chore | Points |
|---|---|
| Mettre la table 🍽️ | 5 |
| Desservir la table 🧽 | 5 |
| Faire son lit 🛏️ | 5 |
| Préparer son sac d'école 🎒 | 5 |
| Ranger sa chambre 🧸 | 10 |
| Vider le lave-vaisselle 🍴 | 10 |
| Sortir le recyclage ♻️ | 10 |

**Settings › Aider à la maison:** a simple list page in the existing settings style: add or edit a chore, its points, which kid, which days, on/off. No Filament for now; the hub's PIN model stays.

---

## 5. Build order (each phase shippable on its own)

1. **Score on approval and generalized tasks.** `task_types`, `tasks` (rename + migrate), `exercise_types` seeded from `TEMPLATES`, the three approval buttons. Useful right away.
2. **Aider à la maison.** `chores`, the kid tile, History approval, settings page.
3. **Skills catalogue.**
   - Hand-encode the PDA items for **2e and 5e** (plus 1re and 4e as prerequisites): about 40–60 math and 20–30 French items (spelling, grammar, conjugation).
   - Store them in `database/data/skills.json` (our own file, citing the PDA) with prerequisites and month windows, plus a seeder.
   - Marble serves only as a reference here.
4. **Exercise variants.** The builders read difficulty `params`. New generators where needed: subtraction with borrowing, fractions with diagrams, conjugation fill-in.
5. **Progress engine.** Variant choice, variety rules, mastery and reviews, the stuck note, and a progress view per kid for the parent (skills by domain, with status).
6. **Later:** the 2027 *liste orthographique* for word lists; reviewed AI exercises; school homework capture from photos (from Oleg's notes, after the above works).

Not taken from the notes for now: separate user accounts, Filament and Livewire. The hub's PIN model and Blade screens cover two kids and one tablet.

---

## 6. Open questions for Oleg

1. **Points on a score:** keep full points for "Quelques erreurs" and half for "À revoir"? Or same points always, with the score only steering difficulty?
2. **Chores:** is the list above right, with these points, per kid? Should chores count toward unlocking tonight's rewards, or only homework?
3. **Month windows:** start from the PDA plus my month estimates, or can you share the teachers' planning, the étape topics, or photos of real homework so the windows match the class?
