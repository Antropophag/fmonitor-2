# Refine sidebar navigation — delivery

Owner request 2026-09-19 initially covered six findings. Investigation proved the canonical `/pilot/calendar` Yii route absent (404), so the owner explicitly split Calendar into GitHub issue #203. This change now contains only bounded presentation behavior: shlz-ui icons; discoverable collapse/expand; separate OTIZ group; installers under directories; floating bottom-right feedback outside navigation.

Root authored `YII2-SIDEBAR-NAVIGATION-002`, OpenSpec artifacts and test delta. Implementation is reserved for a separate executor; final review remains independent.

Focused container RED from base `d0eaf5c58b38f79fcc3de41af6a17cf590055dc1` / executable source `542413726916841f220e550c9898bef635c2d4d404741e17797040736b42e71c`:

`tools/delivery/run-in-profile browser --with-services php tests/Yii2/yii2_main_navigation_001_test.php` → exit 255 at `INTENDED_RED exact permitted MAIN membership and labels on /pilot/objects`: expected Calendar and no feedback nav item; incumbent had no Calendar and retained feedback in nav. Earlier direct host runs were setup failures (missing worktree vendor, then pre-assertion 503) and are not RED evidence.

The first prepared package returned `planner_not_fast` only while Calendar restoration was in scope and is superseded. Gate 3 also returned the first test candidate for stronger group, icon, collapse and overlay sensitivity; those corrections are retained in the current test delta. A fresh plan after the owner split controls the remaining delivery.

After the split, independent review accepted the exact group/child/order correction and requested two final sensitivities. The PHP test now compares every rendered nav/chat/left-right-chevron path geometry against the corresponding pinned `shlz-ui` export, rather than trusting classes or data markers. A read-only host Chromium probe against the incumbent working stand logged both desktop 1280 and mobile 360: `navFeedback=1`, `fab=0`, collapse trigger present but `aria=null` and `icon=null`. Thus the browser expectations for feedback relocation and an explicit state affordance are behavior-RED; the earlier disposable Linux browser profile failure was only missing shared libraries and is not treated as RED.

Gate 5 rereview APPROVED exact package `20260919T192555Z-77de5db890`, candidate source `72ea92f156ee4378fbc0dfa40f4c662e74ca2b0802f2bdc7de0b6aba5d8464b4`. Focused HTTP matrix GREEN with executable source `544008556df5c1c0515fc5b062111015dd55d60857d9924e48fe7b7a0cacda64`; PHP/Node syntax and `git diff --check` GREEN; Impeccable detector `[]`. Disposable exact candidate runtime passed host Chromium desktop/mobile toggle, persistence and floating-feedback geometry; its project and volumes were then removed. CI/publication/deployment remain pending until recorded below.
