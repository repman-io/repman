<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Api;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

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
            ->add('repository', TextType::class, ['constraints' => [new NotBlank()]])
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
                'constraints' => [
                    new NotBlank(),
                ],
            ]);
    }
}
