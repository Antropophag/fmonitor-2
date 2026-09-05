<?php
declare(strict_types=1);

namespace FMonitor2\Tests\Support;

use DOMDocument;
use DOMNode;
use DOMXPath;

/** Fixed fictional admission fixture; PILOT-E2E-RBAC-FIXTURES-001 amendment v3. */
final class ProtectedE2eAdmissionOracle
{
    public static function matches(string $html): bool
    {
        if ($html === '' || preg_match('//u', $html) !== 1) {
            return false;
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET)) {
                return false;
            }
            $xpath = new DOMXPath($document);
            $mains = $xpath->query('//main[@id="main-content"]');
            if ($mains->length !== 1) {
                return false;
            }
            $main = $mains->item(0);
            if ($xpath->query('.//table | .//*[contains(concat(" ", normalize-space(@class), " "), " shlz-table-wrap ")]', $main)->length !== 0) {
                return false;
            }
            $links = $xpath->query('.//a[@href="/pilot/objects/4512"]', $main);
            if ($links->length !== 1 || self::projectText($links->item(0), $xpath) !== '4512') {
                return false;
            }
            $items = $xpath->query('.//li[parent::ul or parent::ol][.//a[@href="/pilot/objects/4512"]]', $main);
            if ($items->length !== 1) {
                return false;
            }
            $text = self::projectText($items->item(0), $xpath);
            foreach (['77-000123', 'Москва, ул. Примерная, д. 10', 'Подъезд 2', '2026-10-05', '2026-12-20'] as $literal) {
                if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($literal, '/') . '(?![\p{L}\p{N}])/u', $text) !== 1) {
                    return false;
                }
            }
            return true;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function projectText(DOMNode $node, DOMXPath $xpath): string
    {
        $runs = [];
        foreach ($xpath->query('.//text()', $node) as $text) {
            $run = trim(preg_replace('/[\p{Z}\s]+/u', ' ', $text->nodeValue), ' ');
            if ($run !== '') {
                $runs[] = $run;
            }
        }
        return implode(' ', $runs);
    }
}
