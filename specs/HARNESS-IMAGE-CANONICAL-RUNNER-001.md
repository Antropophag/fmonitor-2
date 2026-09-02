# HARNESS-IMAGE-CANONICAL-RUNNER-001 v0.1

Status: approved by the TEST-USER-READY reproducible-deployment mission. This packaging contract does not switch container startup to canonical migrations and changes no product behavior.

## Actor and intent

A deployment operator needs the production pilot image to contain the same canonical migration CLI verified from a clean checkout, so a later deployment orchestrator can run migrations before starting runtime consumers.

## Public seam

The repository `Dockerfile`, observed through a freshly built runtime image and command:

`docker run --rm --entrypoint php <image> bin/fmonitor2-migrate.php`

## Contract

1. The runtime image SHALL copy repository `bin/` into `/workspace/fmonitor-2/bin/` without generating or rewriting the migration runner.
2. `/workspace/fmonitor-2/bin/fmonitor2-migrate.php` and its `app/InstallationProcess` dependencies SHALL be readable by the non-root runtime user `fmonitor`.
3. Invoking the runner inside the image with migration environment absent SHALL execute the real CLI, exit `64`, emit exactly `{"ok":false,"reason":"CONFIGURATION_INVALID"}` plus newline on stdout, and keep stderr empty.
4. The image SHALL retain `USER fmonitor` and the existing rapid-pilot entrypoint; this slice packages the runner but SHALL NOT invoke it implicitly at image build or container startup.
   The approved pre-change entrypoint SHA-256 is `61fa6249f6aee6866f662e2cc487382b15cde602444039a9fec155aff385b33d`. The approved pre-change Dockerfile SHA-256 is `6489de91f81d26d5be615e597d6d7503a4edc580b7e726a29327325f96ba8702`; after removing the single canonical `COPY bin ./bin` instruction, its bytes SHALL retain that hash. These pins make the packaging-only delta observable without attempting an incomplete shell call graph.
5. The image SHALL not copy repository `tests/`, `reviews/`, `specs/`, `docs/`, `tools/`, `.local/`, `.git/`, `.env*`, database-dump/backup files, private keys/certificates, or primary `.msg` evidence as part of this change. Existing application SQL schema resources and source files whose names describe secret-handling are not secret material by name alone.
6. Build/setup failure is classified separately from assertion drift by the executable verifier.

## Acceptance scenarios

### Built image exposes the canonical runner

- **GIVEN** a fresh image built from the repository Dockerfile
- **WHEN** the operator overrides the entrypoint and runs `php bin/fmonitor2-migrate.php` without configuration
- **THEN** the real CLI returns its exact configuration-invalid JSON and exit `64`
- **AND** stderr is empty

### Packaging does not alter runtime startup

- **WHEN** the Dockerfile is inspected for this slice
- **THEN** it still declares `USER fmonitor`
- **AND** `ENTRYPOINT ["rapid-pilot/docker-entrypoint.sh"]`
- **AND** no build or entrypoint instruction invokes the migration runner

## Done definition

A reviewed RED fails because the built runtime image lacks `bin/fmonitor2-migrate.php`; the minimal Dockerfile change copies `bin/`; focused image verification, deployment characterization, lint, architecture and diff checks pass; a fresh independent code review is approved.
