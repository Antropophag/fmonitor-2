# TEST ONLY — DO NOT MERGE

This disposable PR verifies the installed Quality Graph publisher after bootstrap
PR73. Its first head is an ordinary documentation-only change: the real accepted
CI selection must run fast checks and honestly skip the four test categories.

Later diagnostic heads may simulate job outcomes to test publication/admission;
those heads are transport fixtures, not evidence of passing production tests.
The PR must remain draft and must never be merged. Application data and runtime
are not modified by this proof.
