<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;

final class SendResetPasswordLinkType extends AbstractType
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
            ->add('email', EmailType::class, ['constraints' => [
                new NotNull(),
                new Email(['mode' => 'html5']),
            ]])
            ->add('send', SubmitType::class, ['label' => 'Email me a password reset link', 'attr' => ['class' => 'btn-primary btn-block']])
        ;
    }
}
