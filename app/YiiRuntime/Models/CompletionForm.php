<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Models;

final readonly class CompletionForm
{
    private function __construct(public array $fields) {}

    public static function parse(string $contentType, string $body, mixed $contentLength): self
    {
        if (preg_match('#^application/x-www-form-urlencoded(?:;\s*charset=UTF-8)?$#iD', $contentType) !== 1) {
            throw new \InvalidArgumentException();
        }
        if (strlen($body) > 16384) throw new \LengthException();
        if ($contentLength !== null && (!is_string($contentLength) || !ctype_digit($contentLength) || (int) $contentLength !== strlen($body))) {
            throw new \InvalidArgumentException();
        }
        $fields = [];
        foreach (explode('&', $body) as $member) {
            if ($member === '') continue;
            [$rawKey, $rawValue] = array_pad(explode('=', $member, 2), 2, '');
            if (preg_match('/%(?![0-9A-Fa-f]{2})/', $rawKey . $rawValue) === 1) throw new \InvalidArgumentException();
            $key = rawurldecode(str_replace('+', ' ', $rawKey));
            $value = rawurldecode(str_replace('+', ' ', $rawValue));
            if ($key === '' || str_contains($key, '[') || str_contains($key, "\0") || str_contains($value, "\0") || array_key_exists($key, $fields)) {
                throw new \InvalidArgumentException();
            }
            $fields[$key] = $value;
        }
        return new self($fields);
    }

    /** @return array{action:string,type:string,capability:string,date:string,details:string,factId:?int,reason:string} */
    public function command(string $today): array
    {
        $action = $this->fields['action'] ?? '';
        if (!in_array($action, ['record_pto', 'record_declaration', 'correct_pto', 'correct_declaration'], true)) {
            throw new CompletionFormError('Неизвестное действие.');
        }
        $pto = str_contains($action, 'pto');
        $date = trim($this->fields[$pto ? 'ptoActDate' : 'declarationDate'] ?? '');
        $match = [];
        $validDate = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $match) === 1
            && checkdate((int) ($match[2] ?? 0), (int) ($match[3] ?? 0), (int) ($match[1] ?? 0)) && $date <= $today;
        $dateMessage = $pto ? 'Укажите дату акта ПТО не позже сегодняшней.' : 'Укажите дату и реквизиты декларации.';
        if (!$validDate) throw new CompletionFormError($dateMessage);
        $details = $pto ? '' : trim($this->fields['declarationDetails'] ?? '');
        if (!$pto && (($action === 'record_declaration' && $details === '') || mb_strlen($details) > 500)) {
            throw new CompletionFormError($dateMessage);
        }
        $factId = null;
        $reason = '';
        if (str_starts_with($action, 'correct_')) {
            $rawFactId = $this->fields['factId'] ?? '';
            $factId = preg_match('/^[1-9][0-9]*$/D', $rawFactId) === 1
                ? filter_var($rawFactId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            $reason = trim($this->fields['reason'] ?? '');
            if ($factId === false || $reason === '' || mb_strlen($reason) > 1000) {
                throw new CompletionFormError('Действие отклонено. Проверьте актуальные данные и повторите.');
            }
        }
        return [
            'action' => $action,
            'type' => $pto ? 'pto_act' : 'declaration',
            'capability' => 'installation.completion.' . ($pto ? 'pto.' : 'declaration.') . (str_starts_with($action, 'record_') ? 'record' : 'correct'),
            'date' => $date,
            'details' => $details,
            'factId' => is_int($factId) ? $factId : null,
            'reason' => $reason,
        ];
    }
}

final class CompletionFormError extends \InvalidArgumentException {}
