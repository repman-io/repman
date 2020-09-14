<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Api;

use Buddy\Repman\Validator\AliasNotBlank;
use Buddy\Repman\Validator\UniqueOrganization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreateOrganizationType extends AbstractType
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
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'max' => 80,
                    ]),
                    new UniqueOrganization(),
                    new AliasNotBlank(),
                ],
                'documentation' => [
                    'maxLength' => 80,
                ],
            ]);
    }
}
