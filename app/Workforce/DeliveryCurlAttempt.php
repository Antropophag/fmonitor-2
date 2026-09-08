<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

/** @internal A single native HTTPS attempt; every handle dies before returning. */
final class DeliveryCurlAttempt
{
    public static function run(
        BitrixWorkforceDeliveryConfig $c, string $url, int $start,
        DeliveryDeadline $deadline, DeliveryBodyBudget $budget, int &$attempts,
    ): array {
        $body = '';
        $curl = curl_init();
        if ($curl === false) throw new DeliveryFailure(BitrixWorkforceDeliveryReason::ConfigurationUnavailable);
        try {
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'sort' => 'ID', 'order' => 'ASC', 'FILTER' => ['UF_DEPARTMENT' => $c->departmentIds],
                    'start' => $start, 'select' => DeliveryPage::FIELDS,
                ], JSON_THROW_ON_ERROR),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_MAXREDIRS => 0,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*', CURLOPT_NETRC => CURL_NETRC_IGNORED,
                CURLOPT_VERBOSE => false, CURLOPT_NOSIGNAL => true, CURLOPT_ENCODING => '',
                CURLOPT_WRITEFUNCTION => static function (\CurlHandle $handle, string $chunk) use (&$body, $budget): int {
                    $length = strlen($chunk);
                    if (!$budget->accept(strlen($body), $length)) return 0;
                    $body .= $chunk;
                    return $length;
                },
            ];
            if ($c->caFile !== null) $options[CURLOPT_CAINFO] = $c->caFile;
            $remaining = $deadline->milliseconds();
            $options[CURLOPT_CONNECTTIMEOUT_MS] = min($c->connectTimeoutSeconds * 1000, $remaining);
            $options[CURLOPT_TIMEOUT_MS] = min($c->requestTimeoutSeconds * 1000, $remaining);
            if (!curl_setopt_array($curl, $options)) throw new DeliveryFailure(BitrixWorkforceDeliveryReason::ConfigurationUnavailable);
            $attempts++;
            $ok = curl_exec($curl);
            $error = curl_errno($curl);
            $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $pretransfer = curl_getinfo($curl, CURLINFO_PRETRANSFER_TIME_T);
            $tlsReady = curl_getinfo($curl, CURLINFO_APPCONNECT_TIME) > 0.0;
            if ($budget->exceeded) throw new DeliveryFailure(BitrixWorkforceDeliveryReason::LimitExceeded);
            $deadline->check();
            return ['ok' => $ok !== false, 'error' => $error, 'status' => $status, 'pretransfer' => $pretransfer, 'tlsReady' => $tlsReady, 'body' => $body];
        } finally {
            // CurlHandle destruction closes the native resource, without PHP8.5 curl_close deprecation.
            unset($curl);
        }
    }
}
