<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\Organization\Member;

use Buddy\Repman\Message\Organization\Member\InviteUser;
use Buddy\Repman\Repository\OrganizationRepository;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Service\Mailer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class InviteUserHandler implements MessageHandlerInterface
{
    private OrganizationRepository $organizations;
    private Mailer $mailer;
    private UserRepository $users;

    public function __construct(
        OrganizationRepository $organizations,
        Mailer $mailer,
        UserRepository $users
    )
    {
        $this->organizations = $organizations;
        $this->mailer = $mailer;
        $this->users = $users;
    }

    public function __invoke(InviteUser $message): void
    {
        $organization = $this->organizations->getById(Uuid::fromString($message->organizationId()));
        if ($organization->inviteUser($message->email(), $message->role(), $message->token())) {
            try {
                $user = $this->users->getByEmail($message->email());
                $roles = $user->roles();

                if(in_array('ROLE_ADMIN', $roles)) {
                    $organization->acceptInvitation($message->token(), $user);
                } else {
                    $this->mailer->sendInvitationToOrganization($message->email(), $message->token(), $organization->name());
                }
            }
            catch (\InvalidArgumentException $e) {
                $this->mailer->sendInvitationToOrganization($message->email(), $message->token(), $organization->name());
            }
        }
    }
}
