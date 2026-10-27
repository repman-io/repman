<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type;

use Buddy\Repman\Validator\Recaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Google reCAPTCHA v2 checkbox. The widget posts its token as "g-recaptcha-response" at the top
 * level of the request, outside the form's own fields, which is where the validator reads it.
 *
 * @extends AbstractType<mixed>
 */
final class RecaptchaType extends AbstractType
{
    public function __construct(private bool $enabled, private string $siteKey)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['recaptcha_enabled'] = $this->enabled;
        $view->vars['recaptcha_site_key'] = $this->siteKey;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'mapped' => false,
            'required' => false,
            'compound' => false,
            'constraints' => [new Recaptcha()],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'recaptcha';
    }
}
