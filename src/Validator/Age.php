<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Age extends Constraint
{

    public $min = false;
    public $max = false;

    public $message = 'Your age must be between {{ min }} and {{ max }}.';

}
