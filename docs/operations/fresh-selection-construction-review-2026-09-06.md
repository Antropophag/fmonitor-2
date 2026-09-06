# Selection construction amendments — independent review history

Reviewer `/root/fresh_selection_gate1`, gpt-5.6-sol low/fork none; author root.

## v0.10 construction — APPROVED

Exact HEAD `e907bb0e9ced135f2d091a16ffa729d1c74e2892`, diff d5abc92..e907bb0;
spec SHAfff6fb08368ba14aab75329e81fbadb0e072bcaf0b4f573ca430e6e78876a2e6.
Seven existing typed ports и I/O-free public factory provide stable construction.
Typo engineer_required исправлен на existing control_engineer_required. Unaffected
v0.9 behavior approval reused. Source/tests ещё отсутствовали; reviewer не автор.

## Follow-up construction blocker

Root обнаружил, reviewer независимо подтвердил: case-NOT_FOUND возникает до
наличия caseId, но прежний case UoW требует existing case, terminal reader read-only,
independent audit writer не создаёт terminal request. Поэтому requirement atomic
terminal+audit для object_not_found не имел исполнимого пути. Fake caseId0 и
uncached отказ недопустимы. Пока не принят amendment, RED этой ветки не разрешён.

Reviewer рекомендовал отдельный typed SelectionTerminalAttemptUnitOfWork для
этой единственной ветки с существующими result/recovery mappings, без case lock
или allocation. v0.11 добавляет этот восьмой dependency и требует exact Gate1
reapproval. Предыдущие approvals остаются historical records своих bytes.

## v0.11 terminal route — APPROVED

Reviewer `/root/fresh_selection_gate1` independently APPROVED exact delta
e907bb0..31b328b7d1903b4b6a9f11757f4c71e5688b3ff9; executable spec SHA256
d9fe7f3c47e66d059138117f99609493b8a00b1d4e4166582b3185a8037d5f1e.
Typed port закрывает единственный case-NOT_FOUND construction gap: atomic
request+audit, authorized clock-acquired route, validated echoes, no fake case
или allocation, existing closed UoW/recovery outcomes. Eighth dependency делает
ветку доступной через stable factory. Diffcheck PASS. Unaffected v0.9/v0.10
approvals reused; source/tests ещё отсутствовали, reviewer их не писал.
