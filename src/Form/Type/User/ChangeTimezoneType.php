<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Timezone;

class ChangeTimezoneType extends AbstractType
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
        $zones = [];
        foreach (Timezones::getIds() as $zone) {
            $zones[sprintf('%s %s', Timezones::getName($zone), Timezones::getGmtOffset($zone))] = $zone;
        }

        $builder
            ->add('timezone', ChoiceType::class, [
                'choices' => $zones,
                'label' => false,
                'attr' => [
                    'class' => 'form-control selectpicker',
                    'data-live-search' => 'true',
                    'data-style' => 'btn-secondary',
                    'data-size' => 10,
                ],
                'constraints' => [
                    new NotBlank(),
                    new Timezone(),
                ],
            ])
            ->add('changeTimezone', SubmitType::class, ['label' => 'Change timezone'])
        ;
    }
}
