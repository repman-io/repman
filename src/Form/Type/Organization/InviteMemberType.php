<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Validator\NotOrganizationMember;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;

final class InviteMemberType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotNull(),
                    new Email(['mode' => 'html5']),
                    new NotOrganizationMember(['organizationId' => $options['organizationId']]),
                ],
            ])
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
            ->add('invite', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('organizationId')->addAllowedTypes('organizationId', 'string');
    }
}
