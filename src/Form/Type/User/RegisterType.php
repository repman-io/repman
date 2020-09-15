<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\User;

use Buddy\Repman\Validator\UniqueEmail;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RegisterType extends AbstractType
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
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Email(['mode' => 'html5']),
                    new UniqueEmail(),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match',
                'first_options' => ['label' => 'Password', 'help' => 'at least 6 characters required'],
                'second_options' => ['label' => 'Repeat Password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096, // https://symfony.com/blog/cve-2013-5750-security-issue-in-fosuserbundle-login-form
                    ]),
                ],
            ])
            ->add('g-recaptcha-response', EWZRecaptchaType::class, [
                'label' => false,
                'attr' => [
                    'options' => [
                        'theme' => 'light',
                        'type' => 'image',
                        'size' => 'normal',
                        'defer' => true,
                        'async' => true,
                    ],
                ],
                'mapped' => false,
                'constraints' => [
                    new RecaptchaTrue(),
                ],
            ])
            ->add('register', SubmitType::class, [
                'label' => 'Sign up',
                'attr' => ['class' => 'btn-primary btn-block'],
            ])
        ;
    }
}
