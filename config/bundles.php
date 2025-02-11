<?php

declare(strict_types=1);

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use EWZ\Bundle\RecaptchaBundle\EWZRecaptchaBundle;
use KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle;
use League\FlysystemBundle\FlysystemBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Sentry\SentryBundle\SentryBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

return [
    FrameworkBundle::class => ['all' => true],
    SensioFrameworkExtraBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    TwigExtraBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    SentryBundle::class => ['prod' => true],
    MakerBundle::class => ['dev' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
    DAMADoctrineTestBundle::class => ['test' => true],
    DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    KnpUOAuth2ClientBundle::class => ['all' => true],
    FlysystemBundle::class => ['all' => true],
    NelmioApiDocBundle::class => ['all' => true],
    NelmioCorsBundle::class => ['all' => true],
    EWZRecaptchaBundle::class => ['all' => true],
];
