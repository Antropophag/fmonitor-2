## Context

См. proposal.md. Prepare route сочетает local authorization, read form и
state-changing command; fixture не должен смешивать эти границы.

## Goals / Non-Goals

**Goals:** canonical local actors; separate local permission/process capability;
exact GET/HEAD route mapping; preserved
admission precedence; isolated DB/env/session.

**Non-Goals:** изменение prepare semantics, other route mapping, POST/CSRF/session
protocol, PDF renderer или production persistence.

## Decisions

1. Positive prepare actor получает exact local role permission и отдельную
   existing process capability. Fixtures/assertions называют namespaces
   раздельно, хотя literal совпадает.
2. Negative cases стартуют из explicit env без actor/grant либо с отдельным
   actor, а не мутируют ambient positive env.
3. Fixtures вызывают exact GET/HEAD public seam и проверяют full persistence snapshot;
   private helper/DB side channel не используется как результат.
4. POST/CSRF state-changing command остаётся в существующем process slice; эта
   change не переопределяет его admission.
5. Canonical entrypoint factory владеет optional renderer decorator только в
   explicit test composition path. Normal production construction всегда передаёт
   identity decorator. Test spy получает созданный factory real renderer,
   считает invocation и делегирует ему без изменения bytes/result.
   Manual reconstruction graph, reflection и shadow renderer отклонены:
   они не доказывают canonical production wiring.

   Exact public PHP contract:

   ```php
   interface PrepareFormRendererDecorator
   {
       public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer;
   }

   final class IdentityPrepareFormRendererDecorator implements PrepareFormRendererDecorator
   {
       public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer
       {
           return $renderer;
       }
   }

   final class ProductionPilotHttpEntrypointFactory
   {
       public static function create(
           EnvironmentSource $environment,
           ?PrepareFormRendererDecorator $prepareFormRendererDecorator = null,
       ): PilotHttpEntrypoint {
           throw new LogicException('Contract signature example; composition is specified below.');
       }
   }
   ```

   `null` означает normal production path: factory сама создаёт
   `IdentityPrepareFormRendererDecorator`. В explicit test composition caller
   передаёт spy decorator. В обоих случаях factory сначала создаёт ровно один
   `ProductionPrepareFormRenderer`, затем вызывает decorator `decorate()` ровно
   один раз и передаёт возвращённый `PrepareFormRenderer` во все canonical read/
   coordinator positions. Decorator не получает environment, dependencies или
   entrypoint и не может заменить остальной graph.
6. Unsupported `PUT|PATCH|DELETE` проверяются через public
   fully-delivered HTTP requests. PHP built-in server может прочитать и
   буферизовать body до вызова application, что находится вне
   границы PilotHttp. Поэтом verifier доказывает exact 405,
   `Allow: GET, HEAD, POST`, absence of payload-derived response bytes, exactly
   one composition-time decorator `decorate()` call, zero request-time wrapped
   renderer `render()`/compatibility-render invocations и zero
   DB/process/artifact/session/file mutation,
   а также application admission до authorization/domain/form work. Он не
   заявляет, что transport не читал body, и не вводит hidden
    request-body observation seam.
7. **Upload-first GET не становится upload command.** Fixture проверяет
   presentation v0.2, но GET/HEAD остаётся read-only. Installer picker имеет
   bounded keyboard/ARIA/DOM-only contract; engineer остаётся selectable radio
   с explicit confirmation по product truth. File, CSRF, multipart и submit
   принадлежат отдельной command composition.

Owning production area — PilotHttp route composition, вызывающая stable
IdentityAccess authorization seam before existing process-capability/form read.
InstallationProcess/domain не меняется; tests/support владеют fixtures.
Rapid-pilot остаётся adapter. Architecture baseline не расширяется.

## Risks / Trade-offs

- [Fixture grant шире route] → explicit per-case permission list assertions.
- [Authorization скрывает предметный RED] → separate authorized baseline before rejection matrix.
- [Shared mutable DB] → task-owned namespace and finally cleanup.

## Migration Plan

Пройти Gates 1–5 vertical route+fixture slice; rollback до release возвращает
route wiring и fixture diff, не добавляя dual/legacy fallback.
