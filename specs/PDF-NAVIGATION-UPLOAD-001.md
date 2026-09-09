# PDF-NAVIGATION-UPLOAD-001

Простыми словами: сформированный системой PDF должен приниматься обратно.
Обычная ссылка и открытие страницы по ширине не являются исполняемым содержимым.
Owner2026-09-09: исправить подтверждённый блокер загрузки собственного шаблона.

Public seam: `FMonitorPassivePdfInspector::inspect(bytes)`.
This manual-pilot correction supersedes the blanket URI/OpenAction prohibition
in ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 and PDF-HISTORY-001 only as follows:
- Direct OpenAction page destinations using Fit, FitH, FitV, FitB, FitBH, FitBV,
  XYZ or FitR are accepted. Action dictionaries/indirect actions remain rejected.
- URI action type and direct URI strings with http, https or mailto schemes are
  accepted. Other schemes and indirect URI values remain rejected.
- JavaScript, AA, Launch, attachments, encryption and other existing restrictions
  remain enforced across all revisions and object streams.
- Original bytes, authorization, upload history and composition are unchanged.

Regression uses synthetic PDF objects mirroring the reported template structure;
the user's source file remains outside the repository. Verify the original bytes
locally as additional evidence, without saving a modified copy.

Exact navigation grammar: a direct array starts with a positive object number,
nonnegative generation and R. Fit/FitB take no operands; FitH/FitV/FitBH/FitBV
take one number or null; XYZ takes three numbers or nulls; FitR takes four numbers.
Numbers use ordinary signed PDF decimal syntax. No extra operands, unknown modes,
nested arrays or indirect destination values are admitted by this exception;
otherwise structurally valid documents with these forms return UNSAFE_PDF.
Reference existence remains subject to existing graph validation. This lexical
exception does not add destination-page semantic validation.

URI strings may be literal (including PDF escapes/octal/line continuation) or
hexadecimal. Decode PDF string encoding once, then require case-insensitive
http://, https:// or mailto: followed by at least one non-control, non-whitespace
byte. No leading whitespace, percent-decoding of schemes, empty target, file or
javascript scheme is allowed; these return UNSAFE_PDF. Malformed PDF grammar
continues to return INVALID_PDF. Existing algorithm identity is retained for this
compatibility correction; the above rules supersede its blanket name prohibition.
