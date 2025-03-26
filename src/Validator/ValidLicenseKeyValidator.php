<?php

declare(strict_types=1);

namespace Fastfony\LicenseBundle\Validator;

use Fastfony\LicenseBundle\Security\LicenseChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidLicenseKeyValidator extends ConstraintValidator
{
    public function __construct(
        private LicenseChecker $licenseChecker
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidLicenseKey) {
            throw new UnexpectedTypeException($constraint, ValidLicenseKey::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (false === $this->licenseChecker->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
