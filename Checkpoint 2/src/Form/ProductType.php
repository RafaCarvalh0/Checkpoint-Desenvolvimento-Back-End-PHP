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
                new Assert\NotBlank(message: 'Informe o nome do produto.'),
                new Assert\Length(max: 120, maxMessage: 'O nome deve ter no máximo 120 caracteres.'),
            ]])
            ->add('sku', TextType::class, ['label' => 'SKU', 'constraints' => [
                new Assert\NotBlank(message: 'Informe o SKU do produto.'),
                new Assert\Length(max: 60),
                new Assert\Regex(pattern: '/^[A-Za-z0-9_-]+$/', message: 'O SKU deve conter apenas letras, números, hífen ou sublinhado.'),
                new UniqueProductSku(),
            ]])
            ->add('category', TextType::class, ['label' => 'Categoria', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
            ->add('price', MoneyType::class, ['label' => 'Preço', 'currency' => 'BRL', 'constraints' => [new Assert\PositiveOrZero(message: 'O preço não pode ser negativo.')]])
            ->add('stock', IntegerType::class, ['label' => 'Estoque', 'constraints' => [new Assert\PositiveOrZero(message: 'O estoque não pode ser negativo.')]])
            ->add('status', ChoiceType::class, ['label' => 'Status', 'choices' => ['Ativo' => 'active', 'Inativo' => 'inactive']])
            ->add('description', TextareaType::class, ['label' => 'Descrição', 'required' => false])
            ->add('image', FileType::class, ['label' => 'Imagem', 'required' => false, 'constraints' => [new Assert\File(
                maxSize: '2M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                maxSizeMessage: 'A imagem deve ter no máximo 2 MB.',
                mimeTypesMessage: 'Envie uma imagem JPG, PNG ou WEBP.',
            )]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductFormData::class]);
    }
}
