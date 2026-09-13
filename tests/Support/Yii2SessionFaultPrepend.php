<?php
declare(strict_types=1);

/** Test-only decorator over PHP's native file session handler. */
final class Yii2SessionFaultHandler extends SessionHandler
{
    private function reached(string $operation): void
    {
        $marker = getenv('FMONITOR_TEST_SESSION_FAULT_MARKER');
        if (!is_string($marker) || $marker === '') throw new RuntimeException('Missing test fault marker.');
        file_put_contents($marker, $operation . "\n", FILE_APPEND | LOCK_EX);
    }

    public function write(string $id, string $data): bool
    {
        if (getenv('FMONITOR_TEST_SESSION_FAULT') === 'write') {
            $this->reached('write');
            return false;
        }
        return parent::write($id, $data);
    }

    public function destroy(string $id): bool
    {
        if (getenv('FMONITOR_TEST_SESSION_FAULT') === 'destroy') {
            $this->reached('destroy');
            return false;
        }
        return parent::destroy($id);
    }
}

// Yii's ReliableSession closes explicitly before sending the response. Registering
// PHP's second shutdown close would recreate an empty native session file after
// the injected write failure and make the fixture observe engine cleanup as an
// application commit.
session_set_save_handler(new Yii2SessionFaultHandler(), false);
