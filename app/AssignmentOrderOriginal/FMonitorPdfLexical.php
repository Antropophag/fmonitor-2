<?php

declare(strict_types=1);
namespace FMonitor2\AssignmentOrderOriginal;

/** @internal Byte lexer; string/comment contents never become PDF Name tokens. */
final class FMonitorPdfLexical
{
    private const SPACE = "\x00\x09\x0A\x0C\x0D\x20";
    private const DELIMITERS = '()<>[]{}/%';
    private const ACTIVE = ['JavaScript','JS','OpenAction','AA','Launch','EmbeddedFiles','Filespec','FileAttachment','RichMedia','Movie','Sound','URI','GoToR','SubmitForm','ImportData'];

    /** @return ?array{kind:string,value:string,text:string,start:int,end:int,unsafe:bool} */
    public static function next(string $bytes, int &$cursor): ?array
    {
        $length = strlen($bytes);
        while ($cursor < $length) {
            if (str_contains(self::SPACE, $bytes[$cursor])) { ++$cursor; continue; }
            if ($bytes[$cursor] === '%') { $cursor += strcspn($bytes, "\r\n", $cursor); continue; }
            break;
        }
        if ($cursor >= $length) return null;
        $start = $cursor; $char = $bytes[$cursor++]; $kind = 'delimiter'; $value = $char; $text = $char; $unsafe = false;
        if ($char === '/') {
            $count = strcspn($bytes, self::SPACE.self::DELIMITERS, $cursor);
            $raw = substr($bytes, $cursor, $count); $cursor += $count;
            if (preg_match('/#(?![0-9a-fA-F]{2})|#00/i', $raw)) throw new \UnexpectedValueException('Invalid PDF name.');
            $value = preg_replace_callback('/#([0-9a-fA-F]{2})/', static fn($m) => chr(hexdec($m[1])), $raw);
            $unsafe = in_array($value, self::ACTIVE, true); $kind = 'name';
            $text = '/'.preg_replace_callback('/[^\x21-\x7e]|[()<>\[\]{}\/%#]/', static fn($m) => sprintf('#%02X', ord($m[0])), $value);
        } elseif ($char === '(') {
            $depth = 1;
            while ($cursor < $length && $depth > 0) {
                $cursor += strcspn($bytes, '()\\', $cursor);
                if ($cursor >= $length) break;
                $part = $bytes[$cursor++];
                if ($part === '\\') { if ($cursor >= $length) throw new \UnexpectedValueException('Unclosed PDF string.'); ++$cursor; }
                elseif ($part === '(') ++$depth;
                else --$depth;
            }
            if ($depth !== 0) throw new \UnexpectedValueException('Unclosed PDF string.');
            $kind = 'string'; $value = $text = '()';
        } elseif ($char === '<' && ($bytes[$cursor] ?? '') !== '<') {
            $end = strpos($bytes, '>', $cursor);
            if ($end === false || preg_match('/[^0-9a-fA-F\x00\x09\x0A\x0C\x0D\x20]/', substr($bytes, $cursor, $end - $cursor))) throw new \UnexpectedValueException('Invalid PDF hex string.');
            $cursor = $end + 1; $kind = 'string'; $value = $text = '<>';
        } elseif (($char === '<' || $char === '>') && ($bytes[$cursor] ?? '') === $char) {
            ++$cursor; $value = $text = $char.$char;
        } elseif (!str_contains(self::DELIMITERS, $char)) {
            $cursor += strcspn($bytes, self::SPACE.self::DELIMITERS, $cursor);
            $kind = 'word'; $value = $text = substr($bytes, $start, $cursor - $start);
        }
        return ['kind'=>$kind,'value'=>$value,'text'=>$text,'start'=>$start,'end'=>$cursor,'unsafe'=>$unsafe];
    }

    /** @return array{body:string,unsafe:bool,end:int,next:int,stream:bool} */
    public static function view(string $bytes, int $cursor = 0, bool $physical = false, bool $dictionary = false): array
    {
        $out = ''; $unsafe = false; $stack = ''; $depth = 0; $last = null;
        while (($token = self::next($bytes, $cursor)) !== null) {
            $value = $token['value'];
            if ($physical && $token['kind'] === 'word' && in_array($value, ['stream','endobj'], true)) {
                if ($depth !== 0 || ($value === 'stream' && $last !== '>>')) throw new \UnexpectedValueException('Invalid PDF object boundary.');
                return ['body'=>trim($out),'unsafe'=>$unsafe,'end'=>$token['start'],'next'=>$cursor,'stream'=>$value === 'stream'];
            }
            if ($token['kind'] === 'delimiter') {
                if ($value === '<<' || $value === '[') $stack[$depth++] = $value === '<<' ? 'd' : 'a';
                elseif ($value === '>>' || $value === ']') {
                    if ($depth === 0 || $stack[--$depth] !== ($value === '>>' ? 'd' : 'a')) throw new \UnexpectedValueException('Unbalanced PDF value.');
                } else throw new \UnexpectedValueException('Invalid PDF delimiter.');
            } elseif ($token['kind'] === 'word' && !in_array($value, ['true','false','null','R'], true)
                && preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D', $value) !== 1) {
                throw new \UnexpectedValueException('Invalid PDF value.');
            }
            $unsafe = $unsafe || $token['unsafe']; $out .= $token['text'].' '; $last = $value;
            if ($dictionary && $token['kind'] === 'delimiter' && $value === '>>' && $depth === 0) return ['body'=>trim($out),'unsafe'=>$unsafe,'end'=>$cursor,'next'=>$cursor,'stream'=>false];
        }
        if ($physical || $dictionary || $depth !== 0) throw new \UnexpectedValueException('Incomplete PDF value.');
        return ['body'=>trim($out),'unsafe'=>$unsafe,'end'=>$cursor,'next'=>$cursor,'stream'=>false];
    }
}
