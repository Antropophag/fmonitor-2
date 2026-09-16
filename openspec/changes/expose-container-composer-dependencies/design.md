## Context

См. `proposal.md`. Existing image устанавливает locked Composer dependencies, но existing `run-in-profile` bind-mount'ит checkout в `/workspace`, где Yii entrypoints ожидают `/workspace/vendor/autoload.php`. Попытка вложенного read-only volume доказала противоречие: writable parent bind заставляет Docker создать host mountpoint, а read-only parent не позволяет OCI создать child mount. Owner decision 2026-09-16 разрешает bounded изменение verification source layout.

Harness уже владеет frozen-candidate seam: `review-source.py capture/restore` сохраняет tracked/untracked additions, deletions и modes, а `harness.py source_details().executable_digest` определяет executed source identity. Этот seam переиспользуется без нового snapshot format или identity system.

Owning module — `tools/delivery/run-in-profile`, existing focused-check image recipe и непосредственно связанный execution helper/ignore policy. Разрешённые dependencies — Docker CLI/daemon, existing snapshot tool, immutable pins, manifests и lockfiles. Persistence owner отсутствует: image layers immutable; writable temp/artifact/runtime state disposable или принадлежит existing external service lifecycle. `rapid-pilot` adapter отсутствует и не читается. Production runtime/Compose не меняются.

## Goals / Non-Goals

**Goals:**

- materialize existing frozen candidate snapshot как read-only `/workspace` внутри verification image;
- предоставить repository-relative Yii bootstrap из matching immutable dependency layer;
- связать image/evidence с existing executable source и lock identities;
- исключить host dependency directories/mountpoints и сохранить profiles/argv/exit/evidence.

**Non-Goals:**

- classification slice B, worktree guard slice C, новый snapshot/environment manager/runner;
- production Docker/Compose, CI topology, deployment или application semantics;
- host dependency setup, dependency/version updates, performance/caching optimization.

## Decisions

1. **Existing frozen snapshot становится Docker build context.** Launcher использует `review-source.py capture/restore`; optional explicit existing snapshot позволяет исполнить ранее frozen candidate после host mutation. Перед build он сравнивает materialized `executable_digest` с frozen identity. `git archive HEAD` отклонён, потому что теряет relevant uncommitted/untracked bytes; direct host application bind отклонён из-за доказанного mountpoint conflict.

2. **Multi-layer image composition без host application mount.** Dependency layer устанавливает lock-bound Composer tree по repository-relative final path; следующий layer копирует materialized candidate source, исключая host dependency trees/secrets/Git metadata. Final container запускается `--read-only`; writable `/tmp` и минимальная existing-check artifact area предоставляются отдельным disposable tmpfs. Project source не является mutable test workspace.

3. **Image identity включает frozen executable source и lock-bearing inputs.** Tag/labels связывают recipe, pins, `composer.json`, `composer.lock` и existing harness `executable_digest`. Launcher inspect/check сверяет labels до command. Это execution identity в разрешённом seam, не общий worktree identity guard slice C.

4. **Fail closed до command.** Startup проверяет readable Composer/Yii bootstrap и identity labels. Dependency resolution/network доступны только image preparation stage; execution не имеет fallback на host Composer/vendor, соседний checkout или network install.

5. **Public-route proof A–O.** Test создаёт disposable worktrees и existing snapshots, затем вызывает launcher. Markers, post-freeze mutation, deletion и mode доказывают source semantics; included paths/read-only state доказывают dependency origin; before/after inventory доказывает host cleanliness; distinct candidates доказывают isolation. Existing tests, которые выводили source через checkout-local Git metadata, MAY минимально использовать explicit execution identity env/evidence, поскольку `.git` не является application source.

## Risks / Trade-offs

- [Snapshot capture и host mutation race] → build только restored frozen directory; сравнивать existing executable digest при materialization и image inspect. Harness source-drift evidence остаётся дополнительным контролем.
- [Docker context исторически исключает tests/tools] → Dockerfile-specific ignore policy исключает `.git`, secrets и dependency trees, но включает exact executable candidate files profiles.
- [Read-only root ломает checks с локальными artifacts] → предоставить только documented disposable tmpfs/artifact paths; unexpected source writes должны fail.
- [Composer generated paths зависят от install location] → install генерируется для final `/workspace`, затем candidate source копируется без host vendor; candidate `app/autoload.php` остаётся project owner.
- [Image/source labels могут устареть при cache reuse] → source digest входит в tag/build args и проверяется перед execution.

## Migration Plan

1. Обновить executable contract A–O и newline oracle; получить Gate 3 delta approval и intended RED source-layout gap.
2. Executor заменяет host bind composition на existing snapshot → restored build context → read-only final image.
3. Выполнить focused public-route/profile/source-identity regressions и independent Gate 5.
4. Выполнить один exact-source CI run через existing consumer; merge/deploy/settings не выполнять.

Rollback — удалить bounded source-layout delta и вернуться к предыдущему verification launcher; host/product data migrations отсутствуют.
