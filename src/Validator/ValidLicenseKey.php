<?php

declare(strict_types=1);

namespace Fastfony\LicenseBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidLicenseKey extends Constraint
{
    public const INVALID_MESSAGE = 'The license key is unknow or unvalid.';
    public string $message = self::INVALID_MESSAGE;
}
