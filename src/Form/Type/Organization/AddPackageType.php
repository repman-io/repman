<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class AddPackageType extends AbstractType
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
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'vcs (git,svn,hg)' => 'vcs',
                    'pear' => 'pear',
                    'artifact' => 'artifact',
                    'path' => 'path',
                ],
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('url', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('Add', SubmitType::class);
    }
}
