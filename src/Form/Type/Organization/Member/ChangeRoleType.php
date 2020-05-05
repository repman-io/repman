<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization\Member;

use Buddy\Repman\Entity\Organization\Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;

final class ChangeRoleType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', ChoiceType::class, [
                'choices' => array_combine(Member::availableRoles(), Member::availableRoles()),
                'constraints' => [
                    new NotNull(),
                ],
                'attr' => [
                    'class' => 'form-control selectpicker',
                    'data-style' => 'btn-secondary',
                ],
            ])
            ->add('change', SubmitType::class, [
                'label' => 'Change role',
            ])
        ;
    }
}
