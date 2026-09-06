## Context

Прежняя PHP stream probe теряет Set-Cookie между запросами и запусками.

## Goals / Non-Goals

Сохранить loopback liveness двух путей с повторным anonymous cookie. Не менять
public domain seams, session GC, protected E2E, DB readiness или historical writers.

## Decisions

Operational adapter rapid-pilot/healthcheck.sh делегирует bounded CLI PHP helper;
HTTP transport curl CLI с private cookie jar. Private directory и flock принадлежат
healthcheck; session persistence остаётся за native application owner. Никакого SQL.
Explicit curl package в image. architecture-check проверяет отсутствие нового boundary.

## Risks / Trade-offs

Liveness проходит анонимный login redirect и не доказывает process readiness.
Cookies сохраняются в owned state volume; недоступность fail closed.

## Migration Plan

Gate1→RED→Gate3→GREEN/regression→Gate5/commit. Для старого owned image разрешён
явный operational bind mount только approved probe; без незаметного feature deploy.
