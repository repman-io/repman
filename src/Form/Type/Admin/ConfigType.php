<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
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
            ->add('local_authentication', ChoiceType::class, [
                'choices' => [
                    'allow login and registration' => 'login_and_registration',
                    'allow login, disable registration' => 'login_only',
                    'disabled' => 'disabled',
                ],
                'attr' => [
                    'class' => 'form-control selectpicker',
                    'data-style' => 'btn-secondary',
                ],
            ])
            ->add('oauth_registration', ChoiceType::class, [
                'choices' => [
                    'enabled' => 'enabled',
                    'disabled' => 'disabled',
                ],
                'label' => 'OAuth registration',
                'help' => 'Note: login with OAuth can be set using the OAUTH_* environment variables',
                'attr' => [
                    'class' => 'form-control selectpicker',
                    'data-style' => 'btn-secondary',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Save'])
        ;
    }
}
