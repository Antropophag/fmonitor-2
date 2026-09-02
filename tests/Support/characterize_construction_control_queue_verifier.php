<?php

declare(strict_types=1);

// Gate 2 tracer: the public test-only worker exists, but deliberately has no
// fixture, production HTTP composition or observer-barrier implementation yet.
// Gate 4 must replace this assertion with the minimal reviewed harness.
fwrite(STDERR, "ASSERTION_FAILURE: production HTTP construction-control queue matrix is not implemented\n");
exit(1);
