<?php

declare(strict_types=1);

namespace App\Validator;

use App\Domain\Product\ProductRepositoryInterface;
use App\Form\Model\ProductFormData;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueProductSkuValidator extends ConstraintValidator
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueProductSku) {
            throw new UnexpectedTypeException($constraint, UniqueProductSku::class);
        }
        if (!is_string($value) || trim($value) === '') {
            return;
        }
        $existing = $this->products->findOneBySku($value);
        $formData = $this->context->getRoot();
        if ($existing !== null && (!$formData instanceof ProductFormData || $existing->getId() !== $formData->currentId)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
