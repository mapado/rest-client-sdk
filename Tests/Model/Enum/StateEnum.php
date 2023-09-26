<?php


namespace Mapado\RestClientSdk\Tests\Model\Enum;

enum StateEnum: string {
    case PENDING = 'pending';
    case CREATED = 'created';
    case VALIDATED = 'validated';
    case CANCELLED = 'cancelled';
}