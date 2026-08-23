<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\ProductFormData;
use App\Validator\UniqueProductSku;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nome', 'constraints' => [
                new Assert\NotBlank(message: 'product.name.required'),
                new Assert\Length(max: 120, maxMessage: 'product.name.max_length'),
            ]])
            ->add('sku', TextType::class, ['label' => 'SKU', 'constraints' => [
                new Assert\NotBlank(message: 'product.sku.required'),
                new Assert\Length(max: 60, maxMessage: 'product.sku.max_length'),
                new Assert\Regex(pattern: '/^[A-Za-z0-9_-]+$/', message: 'product.sku.invalid'),
                new UniqueProductSku(),
            ]])
            ->add('category', TextType::class, ['label' => 'Categoria', 'constraints' => [
                new Assert\NotBlank(message: 'product.category.required'),
                new Assert\Length(max: 100, maxMessage: 'product.category.max_length'),
            ]])
            ->add('price', MoneyType::class, ['label' => 'Preço', 'currency' => 'BRL', 'constraints' => [new Assert\PositiveOrZero(message: 'product.price.positive')]])
            ->add('stock', IntegerType::class, ['label' => 'Estoque', 'constraints' => [new Assert\PositiveOrZero(message: 'product.stock.positive')]])
            ->add('status', ChoiceType::class, ['label' => 'Status', 'choices' => ['Ativo' => 'active', 'Inativo' => 'inactive']])
            ->add('description', TextareaType::class, ['label' => 'Descrição', 'required' => false])
            ->add('image', FileType::class, ['label' => 'Imagem', 'required' => false, 'constraints' => [new Assert\File(
                maxSize: '2M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                maxSizeMessage: 'product.image.max_size',
                mimeTypesMessage: 'product.image.mime_type',
            )]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductFormData::class]);
    }
}
