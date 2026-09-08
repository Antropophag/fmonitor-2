# PHP SELECT tokens — independent scanner Gate5

Reviewer `/root/sql_token_review`, gpt-5.6-sol low/fork none; root author.
Verdict **APPROVED — scanner only**, HEAD
`f285ed66d592366114141f85ee218036170175b5`.

Minimal19-line correction separates quoted tokens, masks only complete dotted
atoms for PHP SQL detection and unquoted enum-case/class-constant SELECT forms.
Quoted SQL is retained, the remainder of each line still checked. Fingerprints
use untouched normalized source, pinned same-line digest remains stable.
Baseline/DDL/rapid mutation/ownership/non-PHP behavior unchanged; no allowlist.
No findings; reviewer did not edit files. No application-core scope claimed.

Final terminal evidence `selection-core-final-green-izgviv32` under external
verification root, manifest1123980f62a97801a3db31c17bba7cbf62c637bd3040623e362f5abdf58cec02.
Clean exactHEAD,84commands PASS including32tool tests and architecture7rules;
baseline/core hash identity asserted. Gate3v2 test expectations unchanged.
