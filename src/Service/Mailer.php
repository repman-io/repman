<?php

declare(strict_types=1);

namespace Buddy\Repman\Service;

interface Mailer
{
    public function sendPasswordResetLink(string $email, string $token, string $operatingSystem, string $browser): void;

    public function sendEmailVerification(string $email, string $token): void;

    public function sendInvitationToOrganization(string $email, string $token, string $organizationName): void;

    /**
     * @param string[] $emails
     * @param mixed[]  $result
     */
    public function sendScanResult(array $emails, string $packageName, string $packageId, string $organizationAlias, array $result): void;
}
