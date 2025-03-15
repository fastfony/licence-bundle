<?php

declare(strict_types=1);

namespace Fastfony\LicenceBundle\Validator;

use Fastfony\LicenceBundle\Security\LicenceChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidLicenceKeyValidator extends ConstraintValidator
{
    public function __construct(
        private LicenceChecker $licenceChecker
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidLicenceKey) {
            throw new UnexpectedTypeException($constraint, ValidLicenceKey::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (false === $this->licenceChecker->isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
