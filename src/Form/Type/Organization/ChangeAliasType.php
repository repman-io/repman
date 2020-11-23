<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Buddy\Repman\Validator\UniqueOrganization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ChangeAliasType extends AbstractType
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
            ->add('alias', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'max' => 80,
                    ]),
                    new UniqueOrganization(),
                    new Regex(['pattern' => '/^[a-z0-9_-]+$/', 'message' => 'Alias can contain only alphanumeric characters and _ or - sign']),
                ],
            ])
            ->add('Change', SubmitType::class)
        ;
    }
}
