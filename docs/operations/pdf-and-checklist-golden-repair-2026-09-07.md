# Generated PDF and checklist repairs; synthetic golden-path receipt

## Generated template rejected by original upload

The owner's latest PDF in Downloads was read without modification. Native
inspection returned `unsafe_pdf` for its 101087 bytes; the active PDF Name kinds
were exactly `URI` and `OpenAction`. A separately generated synthetic template
reproduced the same rejection. TCPDF injected a vendor hyperlink and a default
viewer-opening action. This was a generator/profile incompatibility, not evidence
that the owner's document was malicious.

The renderer now disables those two generator defaults. The PDF inspector and its
denylist are unchanged. Existing downloaded PDFs are not rewritten. The new
renderer-to-inspector regression passes, as does the prior semantic PDF flow.

Private browser runner `runtime/template-original-browser-fixture.php` executed
actual synthetic login, modal selection, composition save, the template-download
button, and upload of those exact downloaded bytes. Result: 100454-byte template,
original HTTP 201, no console/page errors. All three generated PDF pages were
rendered with macOS CoreGraphics and inspected: text, tables and signatures remain
legible and within the page. User source bytes remain outside the repository.

## Checklist initialization and online bulk operations

Real-stand GET `/pilot/assets/checklist.js` returned 503 because the router demanded
an obsolete unminified source fragment before serving the current minified asset.
The router now serves the committed source bytes unchanged.

Once that asset loaded, the actual browser exposed null-control exceptions: the
work controller also initialized the documentary section, which has no work-item
controls. It now selects interactive work sections while preserving the separately
rendered documentary closeout.

The ordinary “complete whole section” action then exposed native revision conflicts:
the first item was accepted and subsequent items reused its base revision. Online
bulk creation now awaits its preceding synchronization. The executable DOM/network
regression observes base revisions `[0,1,2]`; no server stale-revision rule was
relaxed and no already-sent payload is rewritten. Offline queues prepared against
one old revision and concurrently initiated separate clicks are not claimed fixed.

## Full synthetic browser result

Private `runtime/golden-browser-fixture.php` and `runtime/golden-browser.cjs` use
a uniquely owned synthetic database and separate FKR/engineer sessions. The
fixture's work template matches the shipped definitions, with independently
asserted total work weight 85. Real browser actions passed:

1. Select two installers through bounded search and save the composition.
2. Upload original (201), apply composition, correct original (201), reapply.
3. Open separately with actual start date.
4. Complete 41 work items in seven sections and upload seven distinct photos.
5. Observe accepted section-completion responses; reload and verify 41 items,
   seven photos, seven completed sections, work progress 85.
6. Record PTO and mandatory declaration; observe progress 100 after reload.

Result: PASS, zero page/console/network failures. Desktop checklist and mobile
completed-card screenshots were inspected. A prior harness wait used obsolete
sync copy; it was corrected to the published sync state. The harness also waits
for the actual section-completion acknowledgement before the next section.
Synthetic servers/databases are cleaned; no real installation was opened for this
check. This is not a real-stand business-mutation or restart receipt.

Focused asset, online bulk, PDF, existing endpoint/semantic tests, visual/focus
checks and changed-JS detector pass. Independent focused reviews are recorded in
`reviews/code/checklist-initialization-manual-2026-09-07.md` and
`reviews/code/generated-template-passive-pdf-manual-2026-09-07.md`.
Full gates, make verify, CI, other approved sections and production readiness
remain outstanding; the overall goal remains active.
