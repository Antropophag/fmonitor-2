<?php

declare(strict_types=1);

namespace FMonitor2\Tests\Support;

/**
 * Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v6.
 *
 * Data-only oracles are deliberately independent from production parsers,
 * repositories and storage adapters. Production code must not depend on this
 * verifier support file.
 */
final class AssignmentOrderOriginalRemainingMatrix
{
    public const POSITIVE_PDF_BASE64 = 'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK';
    public const POSITIVE_PDF_SHA256 = '4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784';
    public const POSITIVE_PDF_SIZE = 327;
    public const MAX_RECEIVED_BYTES = 20_971_520;
    public const MAX_CHUNK_BYTES = 65_536;

}
