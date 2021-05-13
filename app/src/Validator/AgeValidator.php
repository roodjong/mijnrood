<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use DateTime;

class AgeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof age) {
            throw new UnexpectedTypeException($constraint, Age::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof DateTime)) {
            throw new UnexpectedValueException($value, 'DateTime');
        }

        $now = new DateTime();
        $age = $value->diff($now, true)->y;
        if (($constraint->max !== false && $age > $constraint->max) || ($constraint->min !== false && $age < $constraint->min)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ min }}', $constraint->min)
                ->setParameter('{{ max }}', $constraint->max)
                ->addViolation();
        }
    }
}
