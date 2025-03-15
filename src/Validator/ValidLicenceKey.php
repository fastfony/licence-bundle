<?php

declare(strict_types=1);

namespace Fastfony\LicenceBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidLicenceKey extends Constraint
{
    public const INVALID_MESSAGE = 'The licence key is unknow or unvalid.';
    public string $message = self::INVALID_MESSAGE;
}
