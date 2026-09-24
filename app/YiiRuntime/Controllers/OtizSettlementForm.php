<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

final class OtizSettlementForm
{
    public const FLASH_KEY = 'otiz-settlement-form';
    private const MAX_CENTS = '1000000000000';

    public static function fromPost(int $snapshotId, int $objectId, array $post): array
    {
        $discipline = is_string($post['discipline'] ?? null) ? $post['discipline'] : '';
        $basis = is_string($post['basis'] ?? null) ? $post['basis'] : '';
        $artifact = is_string($post['artifact'] ?? null) ? $post['artifact'] : '';
        $operationId = is_string($post['operationId'] ?? null) ? $post['operationId'] : '';
        $errors = [];
        $cents = self::cents($discipline);
        if ($cents === null) $errors['discipline'] = 'Введите положительную сумму в рублях, например 1000,50 или 1 000,50.';
        if (trim($basis) === '' || mb_strlen($basis) > 500) $errors['basis'] = 'Укажите основание длиной не более 500 символов.';
        if (mb_strlen($artifact) > 300) $errors['artifact'] = 'Документ должен содержать не более 300 символов.';
        return [
            'snapshotId' => $snapshotId,
            'objectId' => $objectId,
            'operationId' => $operationId,
            'discipline' => $discipline,
            'basis' => $basis,
            'artifact' => $artifact,
            'errors' => $errors,
            'cents' => $cents,
        ];
    }

    public static function cents(mixed $value): ?int
    {
        if (!is_string($value)) return null;
        if (preg_match('/^(?:(0|[1-9][0-9]*)|([1-9][0-9]{0,2}(?: [0-9]{3})+))([.,]([0-9]{1,2}))?$/D', $value, $match) !== 1) return null;
        $whole = str_replace(' ', '', $match[1] !== '' ? $match[1] : $match[2]);
        $fraction = ($match[4] ?? '') === '' ? '00' : str_pad($match[4], 2, '0');
        $cents = ltrim($whole.$fraction, '0');
        if ($cents === '') return null;
        if (strlen($cents) > strlen(self::MAX_CENTS) || (strlen($cents) === strlen(self::MAX_CENTS) && strcmp($cents, self::MAX_CENTS) > 0)) return null;
        return (int)$cents;
    }

    public static function domainError(array $state, string $code): array
    {
        $messages = [
            'AMOUNT_UNAVAILABLE' => 'Указанная сумма превышает доступную по объекту.',
            'OBJECT_BLOCKED' => 'По объекту есть блокирующие замечания; удержание сейчас недоступно.',
            'SNAPSHOT_NOT_ACCEPTED' => 'Расчёт ещё не подтверждён.',
            'OPERATION_CONFLICT' => 'Эта операция уже использована с другими данными. Проверьте форму перед следующим действием.',
            'INVALID_COMMAND' => 'Проверьте заполнение формы.',
        ];
        $state['errors']['form'] = $messages[$code] ?? 'Действие не выполнено. Проверьте доступную сумму и состояние объекта.';
        return $state;
    }
}
