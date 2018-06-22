<?php

declare(strict_types=1);

namespace Ypf\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class VoidLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = [])
    {
        // Emptiness...
    }
}
