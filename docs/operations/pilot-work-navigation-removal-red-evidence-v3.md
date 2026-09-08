# PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2 Gate 2 RED

- Executable spec: `ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65`
- Exhaustive renderer test: `c29835d0c9328ebcff33a435da91b2698c76c65ba35fcc1274545ebc4f242ac6`
- Root HTTP sentinel: `pilot_http_auth_001_test.php` at `9de51a02c3a3900112c853a5f4dfb55c6195f93a7f4dc127d0e7b86268ba716b`
- Object-list HTTP/RBAC sentinel: `pilot_object_list_001_test.php` at `861462feb34df7eb107167c314f5605ab1c5e554bb88c1706c0240c05e624f9a`
- Existing wiring suites: object-card, prepare-form, inspection-item endpoint, inspection-planning runtime, route-CSP inventory, installer/catalog and identity-access runtime tests.

The renderer test exercises all ten exact current-screen values for minimal/broad actors and now pins predecessor SHA-256 bytes of every remaining navigation child, including SVG/icon markup, accessibility attributes, current/disabled state, destination and order. It remains intended RED at exact work-label absence (`Expected 0 / Actual 2`). Root and object-list real HTTP sentinels independently fail on the same configured navigation item after their route/auth/setup controls; route-specific tests retain their own success/admission/content responsibilities.
