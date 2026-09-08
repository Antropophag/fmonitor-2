# PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 Gate 2 RED v2

- Specification SHA-256: `17d383f8dc12d2f08789f9f2e196cffd50b5dad1166cdd5ca5722b41dc318626`
- Focused renderer test: `7ce465c3a3e15957e8c4b89311dd1ce783c5db93355f97c2d194db5a33bcd870`
- Real HTTP auth test: `9de51a02c3a3900112c853a5f4dfb55c6195f93a7f4dc127d0e7b86268ba716b`
- Real object-list/RBAC test: `861462feb34df7eb107167c314f5605ab1c5e554bb88c1706c0240c05e624f9a`

Focused renderer RED remains `Expected: 0 / Actual: 2`. The real HTTP auth test reaches an authenticated successful root representation, preserves the root 200 content/GET-HEAD/error and zero-environment/dependency controls, then fails only because the work navigation link count is `1` instead of `0`. The object-list HTTP test independently reaches configured RBAC admission and fails on the same visible/accessible/root-destination predecessor. Together they exercise the canonical HTTP and representation seams without relying on the currently broken UI-shell fixture.
