<?php

namespace Laltu\Quasar\Exceptions;

use Exception;

class IpAddressNotFoundException extends Exception
{
    public function __construct(Exception $exp)
    {
        $message = 'IP address not found.' . PHP_EOL . $exp->getMessage();

        parent::__construct($message);
    }
}