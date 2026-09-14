# Non-blocking clean-stand evidence backlog

Не блокирует issue #76 по owner decision 2026-09-14:

- acceptance runner избыточно трактовал Docker `Created` container network/identity observations как runtime invariants;
- fixed disposable HTTP port может конфликтовать с предыдущим rehearsal target;
- synthetic checklist fixture не создаёт canonical active-case provenance/legacy-baseline chain и поэтому получает корректный typed rejection;
- отдельный append-only evidence reporter должен лучше связывать pragmatic continuation с первоначальным package ledger.

Эти пункты не являются production Yii2 runtime defects. Их не исправлять в #76 и не переносить в production interfaces. Production cutover и legacy stand retirement остаются отдельно авторизуемыми действиями.
