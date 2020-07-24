<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Admin;

use Buddy\Repman\Service\Config;
use Buddy\Repman\Service\Telemetry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    private Telemetry $telemetry;

    public function __construct(Telemetry $telemetry)
    {
        $this->telemetry = $telemetry;
    }

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
            ->add(Config::TELEMETRY, ChoiceType::class, [
                'choices' => [
                    Config::TELEMETRY_ENABLED => Config::TELEMETRY_ENABLED,
                    Config::TELEMETRY_DISABLED => Config::TELEMETRY_DISABLED,
                ],
                'help' => "Enable collecting and sending anonymous usage data (<a href=\"{$this->telemetry->docsUrl()}\" target=\"_blank\" rel=\"noopener noreferrer\">more info</a>)",
                'attr' => [
                    'class' => 'form-control selectpicker',
                    'data-style' => 'btn-secondary',
                ],
            ])
            ->add('technical_email', EmailType::class, [
                'required' => false,
                'help' => 'Fill in your email address to receive software updates',
            ])
            ->add('save', SubmitType::class, ['label' => 'Save'])
        ;
    }
}
