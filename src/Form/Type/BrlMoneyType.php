<?php

namespace App\Form\Type;

use App\Form\DataTransformer\BrlMoneyTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** Campo de texto com máscara de R$ (public/js/app.js) que grava o valor como decimal. */
final class BrlMoneyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->resetViewTransformers()->addViewTransformer(new BrlMoneyTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'Informe um valor válido (ex.: 1.234,56).',
            'attr' => ['data-money-mask' => '', 'inputmode' => 'numeric', 'placeholder' => '0,00'],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'brl_money';
    }
}
