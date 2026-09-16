#!/usr/bin/env python3
"""INTENDED-RED-FIXTURE-REACHABILITY-001 bounded acceptance oracle."""
import unittest

from delivery_harness_001_test import Harness


METHODS = [
    'test_fixture_reachability_declaration_is_opt_in_and_fail_closed',
    'test_fixture_reachability_runner_requires_exact_successful_boundary',
    'test_gate3_requires_separate_red_and_fixture_reachability_evidence',
    'test_undeclared_intended_red_compatibility_remains_gate3_admissible',
    'test_repeated_and_concurrent_controls_keep_independent_records',
    'test_fixture_reachability_blocks_forensic_defect_classes_after_real_red',
    'test_realistic_post_fork_db_fixture_has_defective_and_healthy_sensitivity',
]


suite = unittest.TestSuite(Harness(name) for name in METHODS)
result = unittest.TextTestRunner(verbosity=2).run(suite)
if not result.wasSuccessful():
    print('INTENDED_RED: pre-Gate-3 fixture reachability safeguard is absent')
    raise SystemExit(7)
print('INTENDED_RED_FIXTURE_REACHABILITY_001_OK')
