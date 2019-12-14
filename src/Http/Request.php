<?php

declare (strict_types = 1);

namespace Ypf\Http;

class Request
{
    public function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public function isPjax(): bool
    {
        return isset($_SERVER['HTTP_X_PJAX']) && strtolower($_SERVER['HTTP_X_PJAX']) == 'true';
    }
}
