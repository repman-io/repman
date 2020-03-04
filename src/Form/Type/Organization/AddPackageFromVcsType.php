<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddPackageFromVcsType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach ($options['repositories'] as $repo) {
            $choices[$repo] = $repo;
        }

        $builder
            ->add('repositories', ChoiceType::class, [
                'choices' => $choices,
                'label' => false,
                'expanded' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'form-control selectpicker',
                    'data-live-search' => 'true',
                    'data-style' => 'btn-info',
                ],
            ])
            ->add('Import', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'repositories' => [],
        ]);
        $resolver->setAllowedTypes('repositories', 'array');
    }
}
