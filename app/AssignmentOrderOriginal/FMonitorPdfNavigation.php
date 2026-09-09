<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Narrow navigation exceptions to the active-content name filter. */
final class FMonitorPdfNavigation
{
    public static function allowed(string $name, string $bytes, int $cursor, bool $key, bool $uriActionType): bool
    {
        if ($name === 'URI') {
            if ($uriActionType) return true; // /S /URI is the action type.
            if (!$key) return false;
            $token = FMonitorPdfLexical::next($bytes, $cursor);
            if (($token['kind'] ?? '') !== 'string') return false;
            $raw = substr($bytes, $token['start'], $token['end'] - $token['start']);
            if ($raw[0] === '<') {
                $hex = preg_replace('/\s+/', '', substr($raw, 1, -1));
                $uri = hex2bin($hex.(strlen($hex) % 2 ? '0' : ''));
            } else {
                $uri = preg_replace_callback('/\\\\(?:[0-7]{1,3}|\r\n|[\s\S])/', static function ($match) {
                    $escape = substr($match[0], 1);
                    if (preg_match('/^[0-7]{1,3}$/D', $escape)) return chr(octdec($escape) & 255);
                    return match ($escape) { 'n'=>"\n", 'r'=>"\r", 't'=>"\t", 'b'=>"\x08", 'f'=>"\x0c", "\r", "\n", "\r\n"=>'', default=>$escape };
                }, substr($raw, 1, -1));
            }
            return is_string($uri) && preg_match('/^(?:https?:\/\/|mailto:)[^\x00-\x20\x7f]+$/iD', $uri) === 1;
        }
        if ($name !== 'OpenAction' || !$key) return false;
        $parts = [];
        // An explicit destination has at most nine tokens, including brackets.
        for ($i = 0; $i < 10; ++$i) {
            $token = FMonitorPdfLexical::next($bytes, $cursor);
            if ($token === null) return false;
            $parts[] = $token['text'];
            if ($token['text'] === ']') break;
        }
        $coordinate = '[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)';
        $number = '(?:null|'.$coordinate.')';
        return preg_match('/^\[ [1-9][0-9]* [0-9]+ R \/(?:Fit|FitB|(?:FitH|FitV|FitBH|FitBV) '.$number.'|XYZ '.$number.' '.$number.' '.$number.'|FitR '.$coordinate.' '.$coordinate.' '.$coordinate.' '.$coordinate.') \]$/D', implode(' ', $parts)) === 1;
    }
}
