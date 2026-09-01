<?php

declare(strict_types=1);

// IDENTITY-ACCESS-SCHEMA-001 v0.1 section 7.11.
// Authored at Gate 2 under the owner-approved diagnostic-seam amendment.
// These implementation-independent assertions are invoked by the minimal-GREEN
// adapter once public v6 orchestration exists; expectations are not editable at
// GREEN merely to fit an implementation.

/**
 * @param callable():array{exitCode:int,stdout:string,stderr:string} $runUnexpectedV6Failure
 * @param callable(callable():void):array{exitCode:int,stdout:string,stderr:string} $runV6FailureBeforeLaterMigration
 */
function iaAssertGreenApplicationFailureContract(
    callable $runUnexpectedV6Failure,
    callable $runV6FailureBeforeLaterMigration,
): void {
    assertSameValue(
        [70, "{\"ok\":false,\"reason\":\"MIGRATION_FAILED\"}\n", ''],
        array_values($runUnexpectedV6Failure()),
        'Unexpected failure inside v6 is exactly redacted as MIGRATION_FAILED.',
    );

    $laterInvocations = 0;
    $laterMigration = static function () use (&$laterInvocations): void {
        ++$laterInvocations;
    };
    assertSameValue(
        [70, "{\"ok\":false,\"reason\":\"MIGRATION_FAILED\"}\n", ''],
        array_values($runV6FailureBeforeLaterMigration($laterMigration)),
        'Unexpected v6 failure preserves the exact public failure contract.',
    );
    assertSameValue(0, $laterInvocations, 'Application orchestration must stop before every post-v6 migration.');
}
