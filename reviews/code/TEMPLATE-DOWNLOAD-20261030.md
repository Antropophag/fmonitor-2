# Assignment-order template download — independent code review

Reviewer: `/root/otiz_ui_review`, not an author of code or tests.
Verdict: **APPROVED**, 2026-10-30.

The template remains a POST-only, CSRF-protected command with the existing capability,
portal authorization, closed-body validation, generation/audit behavior and PDF bytes.
The response now uses `Content-Disposition: attachment`; removing `target=_blank` lets
the browser download it while retaining the selection page. No GET retry, blank tab,
permission change, fact change or new route is introduced.

Canonical browser evidence proves one POST, HTTP 200 attachment, real `%PDF` bytes,
one download, unchanged selection URL and one browser page. PHP lint and
`git diff --check` pass. Full local suite/CI was not run under the owner-authorized
short production cycle.

## Exact runtime binding

- `151629d9a9d132bb26b490e5a3e2311824e29a1822161d8f8d18a3c7a82350dc` `app/YiiRuntime/Controllers/AssignmentOrderTemplateController.php`
- `fc7a5895c55fc5ddef147d5ae2275e54b14038f6118dcc5750b72e8f54569d01` `app/YiiRuntime/Views/selection.php`
