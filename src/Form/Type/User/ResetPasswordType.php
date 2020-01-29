<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class ResetPasswordType extends AbstractType
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
            ->add('token', HiddenType::class)
            ->add('password', PasswordType::class, ['attr' => ['placeholder' => 'Provide new password']])
            ->add('change', SubmitType::class, ['label' => 'Change password', 'attr' => ['class' => 'btn-primary btn-block']])
        ;
    }
}
