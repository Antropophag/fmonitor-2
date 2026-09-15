# №15 — ссылки на техническую документацию Битрикс

## Authorization and authors

Поручение владельца 2026-09-14: выполнить №15 от актуального main до PR-ready в строго указанном scope. Branch `codex/issue-15-bitrix-order-document-links`, base `b2907355`. Merge и deployment не разрешены.

Root (`/root`) владеет scope, OpenSpec, нормативным контрактом, verification input и executable tests. Реализацию выполняет отдельный `gpt-5.6-sol/low` executor после требуемого Gate 3. Gate reviews выполняют независимые `gpt-5.6-sol/low` agents; фактические имена и packages дополняются по ходу delivery.

## Scope and legacy evidence

Read-only oracle: `../fmonitor/application/controllers/Integration.php` (`create_public_link_folders`, `expandFolderName`), `../fmonitor/application/controllers/Tables.php` (join `installation_drawings_folders.name = fm_maintable.zavnumber`) и `../fmonitor/application/views/tables/helper/showcell.php` (ссылка на `zavnumber`). `regnumber` не является ключом. Legacy weaknesses — отсутствие refresh уже известного `b24id`, неразличимость partial/error и empty, произвольное `MAX(link)` — не переносятся.

См. `specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md` и OpenSpec `bitrix-order-document-links`. Excluded boundaries перечислены в current goal и остаются неизменными.

## Evidence and current status

Planning artifacts complete и `openspec validate --strict` GREEN. Старый active harness binding принадлежит merged №31 и не является evidence №15. Новый verification plan/package, RED, reviews, implementation, CI и PR ещё не выполнены. Live Bitrix behavior/access, CI, deployment и enforcement — `UNKNOWN`, не GREEN.

Первый независимый Gate 1 reviewer `/root/gate1_review` вернул `CHANGES_REQUESTED`: не хватало fail-closed проверки того, что external URL требует Bitrix authentication, canonical tuple/hash semantics, полного run audit и конечной outcome/reason taxonomy. Root исправил контракт: anonymous bodyless challenge admission; exact tuple/canonical JSON/hash/source-ID conflict; immutable time/trigger/actor/source/input/outcome/snapshot audit; stable receipts/reasons и precedence. Требуется свежий независимый Gate 1 rereview.

Первый свежий rereview `/root/gate1_rereview` подтвердил tuple/hash и taxonomy, но вернул `CHANGES_REQUESTED` для точного no-body redirect algorithm и canonical replay input. Root уточнил HEAD-only protocol no-body/no automatic redirects/no GET fallback, acceptance exact login redirect без запроса login body, а также canonical complete/failed input hash с source identity и exact replay/collision semantics. Требуется новое независимое решение.

Третий свежий reviewer `/root/gate1_final_review` вынес `READY_FOR_OWNER_REVIEW`: оба последних finding закрыты, новых blocking findings нет, strict validation PASS. Retained checkpoint: `/Users/antropophag/.local/share/fmonitor-2/issue-15/gate1-final-review-source`. Root принимает scope/spec checkpoint по прямому поручению владельца и открывает Gate 2; авторство spec/tests остаётся root.

Verification input покрывает A1–A7 семью уникальными test consumers и 22 planned production paths. Planner после переноса adapter в существующую `InstallationProcess` boundary выбрал `CRITICAL`, categories governance+unit, `required_reviews=[gate3,final]`; plan check `CHANGE_VERIFICATION_OK`. Независимый `/root/plan_review` подтвердил все 15 delta scenarios, отсутствие duplicates/unknown boundaries и scope exclusions; plan SHA `fef41239…`, input SHA `fd9883fa…`.

Root написал семь mapped tests и общий test fixture. `php -l` PASS для всех. Каждый direct bounded invocation дал intended RED exit 255 на отсутствующем публичном production owner; DB-backed paths успешно создали/очистили изолированную MariaDB до RED. Полный local suite не запускался. Требуется replan и Gate 3 review до executor implementation.

## Current checkpoint — 2026-09-15

По прямому owner instruction контракт сокращён: предыдущие HEAD challenge, runs/snapshots/replay/audit/recovery требования выше superseded и сохранены только как история. Действующий scope: HTTPS exact-origin delivery, exact `zavnumber`, одна transactional current projection, existing object card/console и hourly job через existing Jobs. `rapid-pilot` compatibility не реализуется.

Minimal Gate 1 `/root/minimal_gate1` и Gate 3 `/root/gate3_scope_clean` подтвердили scope и RED sensitivity. Production реализовал отдельный executor `/root/issue15_executor` (`gpt-5.6-sol/low`), tests/specs им не менялись. Exact-source focused plan: 9 acceptance, governance, runtime и architecture checks — GREEN; full local suite не запускался. Первый Gate 5 вернул один применимый finding по empty `zavnumber`, исправленный executor; требование восстановить superseded HEAD challenge отклонено как противоречащее owner scope. GitHub CI и итоговый exact-source review остаются до PR-ready.
