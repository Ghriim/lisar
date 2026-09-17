<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Turns symfony/validator's violation list into the accumulated shape every validator throws.
 *
 * @template T of DataInputInterface
 */
abstract readonly class AbstractBaseValidator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @param T $input
     *
     * @return array<string, list<string>>
     */
    protected function getViolations(DataInputInterface $input): array
    {
        $violations = [];
        foreach ($this->validator->validate($input) as $violation) {
            $violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $violations;
    }
}
