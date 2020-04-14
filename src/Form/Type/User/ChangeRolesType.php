<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangeRolesType extends AbstractType
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
            ->add('admin', CheckboxType::class, [
                'required' => false,
                'label' => 'Administrator',
                'help' => 'allows access to additional administrative functions',
            ])
            ->add('save', SubmitType::class, ['label' => 'Change roles'])
        ;
    }
}
