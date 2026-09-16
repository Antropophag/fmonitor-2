# INTENDED-RED-OBSERVATION-PROVENANCE-001

## Простыми словами

Harness больше не считает ожидаемым RED сбой запуска лишь потому, что текст ожидаемой ошибки повторился в команде или diagnostic wrapper. Intended RED возможен только когда тест реально дошёл до проверяемого поведения и его oracle выдал ожидаемое наблюдение.

## Actor, authorization and public seam

Actor — delivery root/executor/reviewer. Публичный seam — `python3 tools/delivery/harness.py run [--intended-red MARKER] -- ARGV...`, включая supported wrapper route. Запуск не выдаёт product authorization и не создаёт domain/audit facts; retained evidence пишется append-only во внешний harness home.

## Normative outcome contract

Runner различает минимум `SETUP_FAILURE`, `REGRESSION_FAILURE`, `INTENDED_RED`, `GREEN`.

`INTENDED_RED` разрешён только если одновременно:

1. launcher/setup успешно достиг test execution;
2. expected marker получен из разрешённого observation channel фактически исполняемого test oracle;
3. marker относится к заявленному acceptance behavior;
4. marker не происходит только из argv, command echo, serialized command metadata, wrapper diagnostic, environment dump или expected-value echo.

Setup/control outcome имеет приоритет. Setup/launcher failure до behavior даёт `SETUP_FAILURE` либо существующий точный non-RED outcome. Unrelated assertion даёт `REGRESSION_FAILURE`. Exit zero без control failure даёт `GREEN`. Произвольный nonzero не является intended RED.

Для direct acceptance command его фактические stdout/stderr являются разрешённым oracle channel. Для structured wrapper route admission использует явно отделённое child observation; wrapper metadata не является oracle. Повреждённая либо отсутствующая обязательная provenance fail closed для `INTENDED_RED`.

## Evidence contract

Classification не изменяет raw command verdict. Retained record сохраняет raw child return code, normalized exit, stdout/stderr paths, wrapper diagnostic и machine-readable exact outcome. Marker в metadata может оставаться в evidence, но не даёт admission.

Каждый повторный/конкурентный запуск классифицируется только по собственным observation bytes и получает отдельный record. Existing healthy intended-RED fixtures сохраняют lifecycle contract; setup-failure fixtures не становятся regression/intended RED.

## Executable examples

- A: marker только в argv → `REGRESSION_FAILURE`, не `INTENDED_RED`.
- B: marker только в serialized `RUN_IN_PROFILE_RESULT` diagnostic → не `INTENDED_RED`.
- C: marker только в command echo → не `INTENDED_RED`.
- D: launcher/setup failure до behavior → `SETUP_FAILURE`/точный non-RED.
- E: достигнутый oracle выдаёт canonical expected marker → `INTENDED_RED`.
- F: unrelated assertion → `REGRESSION_FAILURE`.
- G: successful behavior → `GREEN`.
- H: marker в metadata и legitimate oracle → `INTENDED_RED`.
- I: exit code и diagnostic сохраняются после classification.
- J: healthy intended-RED fixtures сохраняют lifecycle contract.
- K: setup-failure fixtures не становятся regression/intended RED.
- L: public harness prepare/run route возвращает machine-readable точный outcome.
- M: T08-shaped dependency/setup failure с marker только в wrapper metadata не равен `INTENDED_RED` без hard-code dependency path.

## Explicit non-goals

Container vendor visibility, worktree identity guard, redesign evidence protocol, product tests, FAST/T06/T03, semantic log parsing, LLM, issue-specific/dependency-specific hard-code, `rapid-pilot/`, merge/deployment/settings.
