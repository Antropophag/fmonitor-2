## Context

См. proposal. Current application reference уже validates selected lineage внутри
owned snapshot. Diagnostic EvidenceReader раскрывает inventories и не подходит API.

## Goals / Non-Goals

**Goals:** owning-module paged metadata, exact historical PDF buffer before success.
**Non-Goals:** actor policy, HTTP, application/opening, new schema, lazy external stream.

## Decisions

AssignmentOrderOriginal владеет DB proof и storage layout. Existing source helpers
переиспользуются, diagnostic verifier не вызывается. Revision-number cursor устойчив
к append-only correction; все metadata поля whitelisted и возвращаются by value.
Prepared buffer ограничен20MiB, полностью проверяется до выдачи. Это позволяет
HTTP позже выставить success headers без deferred storage failure. Альтернатива
lazy streaming требует частичных response guarantees и здесь не нужна.

DB snapshot освобождается до FS phase: immutable выбранная revision сохраняется
при новой correction. Read-only shared nonblocking existing digest lease исключает
гонку с native storage maintenance/writer без создания lock/state files. Filesystem
reader проверяет native owner/mode/path/inode/size/hash, возвращая immutable bytes.
Новые классы остаются небольшими; boundary baseline не меняется.

## Risks / Trade-offs

20MiB buffer требует bounded memory → размер проверяется до read, читается не более
expected+1; caller не получает stream/descriptor. Busy lease означает unavailable
и позволяет HTTP повторить позже. Full lineage validation остаётся owning-module
proof, pagination ограничивает response, не ослабляет validation исторических facts.
