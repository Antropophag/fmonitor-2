<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal Public consumers construct this readonly delivery port through its factory. */
final class NativeBitrixWorkforceDeliveryClient implements BitrixWorkforceDeliveryClient
{
    public function __construct(private readonly BitrixWorkforceDeliveryConfig $config, private readonly string $origin) {}

    public function fetch(): BitrixWorkforceDeliveryResult
    {
        $pages = 0;
        $attempts = 0;
        $deadline = null;
        $budget = new DeliveryBodyBudget();
        try {
            $token = DeliveryCredentials::read($this->config);
            $deadline = new DeliveryDeadline($this->config->deadlineSeconds);
            $url = $this->origin.'/rest/'.$this->config->webhookUserId.'/'.$token.'/user.get';
            unset($token);
            $records = [];
            $total = null;
            $lastId = 0;
            $start = 0;
            do {
                $body = $this->page($url, $start, $deadline, $budget, $attempts);
                [$total, $selected, $lastId, $more] = DeliveryPage::read($body, $this->config->departmentIds, $start, $total, $lastId);
                array_push($records, ...$selected);
                $deadline->check();
                $pages++;
                $start += 50;
            } while ($more);
            $batch = new BitrixWorkforceDeliveryBatch($total, $records);
            $deadline->check();
            return new BitrixWorkforceDeliveryResult(null, $pages, $attempts, $batch);
        } catch (\Throwable $error) {
            $reason = $error instanceof DeliveryFailure ? $error->reason : BitrixWorkforceDeliveryReason::ConfigurationUnavailable;
            if ($budget->exceeded || $reason === BitrixWorkforceDeliveryReason::LimitExceeded) {
                $reason = BitrixWorkforceDeliveryReason::LimitExceeded;
            } elseif ($deadline !== null) {
                try { $deadline->check(); } catch (DeliveryFailure) { $reason = BitrixWorkforceDeliveryReason::DeadlineExceeded; }
            }
            return new BitrixWorkforceDeliveryResult($reason, $pages, $attempts, null);
        }
    }

    private function page(string $url, int $start, DeliveryDeadline $deadline, DeliveryBodyBudget $budget, int &$attempts): string
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $result = DeliveryCurlAttempt::run($this->config, $url, $start, $deadline, $budget, $attempts);
            if (!$result['ok']) {
                if ($result['error'] === CURLE_SSL_CACERT_BADFILE) throw new DeliveryFailure(BitrixWorkforceDeliveryReason::ConfigurationUnavailable);
                $retry = $result['error'] === CURLE_OPERATION_TIMEDOUT && (!$result['tlsReady'] || $result['pretransfer'] === 0);
            } else {
                if ($result['status'] === 200) return $result['body'];
                if (in_array($result['status'], [401, 403], true)) throw new DeliveryFailure(BitrixWorkforceDeliveryReason::AuthorizationFailed);
                $retry = in_array($result['status'], [429, 502, 503, 504], true);
            }
            if (!$retry || $attempt === 3) throw new DeliveryFailure(BitrixWorkforceDeliveryReason::TransportFailed);
            $deadline->backoff($attempt);
        }
        throw new DeliveryFailure(BitrixWorkforceDeliveryReason::TransportFailed);
    }
}
