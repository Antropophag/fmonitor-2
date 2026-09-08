<?php
declare(strict_types=1);

namespace FMonitor2\Workforce;

enum BitrixWorkforceDeliveryReason: string
{
    case ConfigurationUnavailable = 'configuration_unavailable';
    case TransportFailed = 'transport_failed';
    case AuthorizationFailed = 'authorization_failed';
    case ApiFailed = 'api_failed';
    case SchemaInvalid = 'schema_invalid';
    case PaginationInvalid = 'pagination_invalid';
    case ScopeInvalid = 'scope_invalid';
    case LimitExceeded = 'limit_exceeded';
    case DeadlineExceeded = 'deadline_exceeded';
}
