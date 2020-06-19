<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangeAnonymousAccessType extends AbstractType
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
            ->add('hasAnonymousAccess', CheckboxType::class, [
                'label' => 'Allow anonymous users',
                'required' => false,
            ])
            ->add('changeAnonymousAccess', SubmitType::class, ['label' => 'Change'])
        ;
    }
}
