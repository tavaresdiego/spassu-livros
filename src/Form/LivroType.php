<?php

namespace App\Form;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use App\Form\Type\BrlMoneyType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LivroType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class, ['label' => 'Título', 'empty_data' => '', 'attr' => ['maxlength' => 40]])
            ->add('editora', TextType::class, ['label' => 'Editora', 'required' => false, 'empty_data' => '', 'attr' => ['maxlength' => 40]])
            ->add('edicao', IntegerType::class, ['label' => 'Edição', 'empty_data' => '1', 'attr' => ['min' => 1]])
            ->add('anoPublicacao', TextType::class, ['label' => 'Ano de publicação', 'empty_data' => '', 'attr' => ['maxlength' => 4, 'inputmode' => 'numeric', 'placeholder' => 'AAAA']])
            ->add('valor', BrlMoneyType::class, ['label' => 'Valor'])
            ->add('autores', EntityType::class, [
                'label' => 'Autores',
                'class' => Autor::class,
                'choice_label' => 'nome',
                'multiple' => true,
                'by_reference' => false,
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('a')->orderBy('a.nome'),
                'help' => 'Digite para buscar; selecione quantos autores precisar.',
                // Select2 (pillbox) ativado por public/js/app.js via [data-pillbox] (data-select2 quebra o plugin); sem JS, select múltiplo comum
                'attr' => ['size' => 8, 'data-pillbox' => '', 'data-placeholder' => 'Selecione os autores'],
            ])
            ->add('assuntos', EntityType::class, [
                'label' => 'Assuntos',
                'class' => Assunto::class,
                'choice_label' => 'descricao',
                'multiple' => true,
                'by_reference' => false,
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('s')->orderBy('s.descricao'),
                'help' => 'Digite para buscar; selecione quantos assuntos precisar.',
                'attr' => ['size' => 8, 'data-pillbox' => '', 'data-placeholder' => 'Selecione os assuntos'],
            ])
            ->add('imagemMobileUrl', UrlType::class, ['label' => 'URL da imagem (mobile)', 'required' => false, 'default_protocol' => null, 'attr' => ['placeholder' => 'https://']])
            ->add('imagemDesktopUrl', UrlType::class, ['label' => 'URL da imagem (desktop)', 'required' => false, 'default_protocol' => null, 'attr' => ['placeholder' => 'https://']])
            ->add('descricao', TextareaType::class, ['label' => 'Descrição', 'required' => false, 'attr' => ['rows' => 5]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Livro::class]);
    }
}
