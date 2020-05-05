<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization\Member;

use Buddy\Repman\Entity\Organization\Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options): void {
                if ((bool) $options['isLastOwner'] && $event->getData()['role'] === Member::ROLE_MEMBER) {
                    $event->getForm()->get('role')->addError(new FormError('The role cannot be downgraded. Organisation must have at least one owner.'));
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('isLastOwner')->addAllowedTypes('isLastOwner', 'bool');
    }
}
