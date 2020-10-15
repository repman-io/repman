<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class AddPackageType extends AbstractType
{
    /**
     * @var string[]
     */
    private array $allowedTypes;

    /**
     * @param string[] $allowedTypes
     */
    public function __construct(array $allowedTypes = [])
    {
        $this->allowedTypes = $allowedTypes;
    }

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
            ->add('formUrl', HiddenType::class, [
                'attr' => ['class' => 'addPackageFormUrl'],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => array_filter([
                    'Git' => 'git',
                    'GitHub' => 'github',
                    'GitLab' => 'gitlab',
                    'Bitbucket' => 'bitbucket',
                    'Mercurial' => 'mercurial',
                    'Subversion' => 'subversion',
                    'Pear' => 'pear',
                    'Artifact' => 'artifact',
                    'Path' => 'path',
                ], fn (string $type): bool => in_array($type, $this->allowedTypes, true)),
                'attr' => [
                    'class' => 'addPackageType form-control selectpicker',
                    'data-live-search' => 'true',
                    'data-style' => 'btn-secondary',
                    'title' => 'select repository type',
                ],
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('keepLastReleases', IntegerType::class, [
                'label' => 'Keep last releases',
                'data' => 0,
                'help' => 'Number of last releases that will be downloaded. Put "0" to download all.',
                'required' => false,
                'constraints' => [
                    new PositiveOrZero(),
                ],
            ])
            ->add('Add', SubmitType::class);
    }
}
