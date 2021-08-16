<?php

declare(strict_types=1);

namespace Buddy\Repman\Tests\Functional\Controller\Organization;

use Buddy\Repman\Entity\Organization\Member;
use Buddy\Repman\Tests\Functional\FunctionalTestCase;

final class MemberControllerTest extends FunctionalTestCase
{
    private string $userId;
    private string $organizationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createAndLoginAdmin();
        $this->organizationId = $this->fixtures->createOrganization('repman', $this->userId);
    }

    public function testInviteMember(): void
    {
        $this->client->request('GET', $this->urlTo('organization_invite_member', ['organization' => 'repman']));
        self::assertTrue($this->client->getResponse()->isOk());

        $this->client->submitForm('Invite', [
            'email' => 'some@buddy.works',
            'role' => Member::ROLE_MEMBER,
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_invitations', ['organization' => 'repman']))
        );

        $this->client->followRedirect();
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('User &quot;some@buddy.works&quot; has been successfully invited', (string) $this->client->getResponse()->getContent());
    }

    public function testRemoveInvitation(): void
    {
        $this->fixtures->inviteUser($this->organizationId, 'some@buddy.works', $token = '04550fd6-47d1-491f-84d6-227d4a1a38e8');
        $this->client->request('DELETE', $this->urlTo('organization_remove_invitation', [
            'organization' => 'repman',
            'token' => $token,
        ]));

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_invitations', ['organization' => 'repman']))
        );

        $this->client->followRedirect();
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('The invitation has been deleted', (string) $this->client->getResponse()->getContent());
    }

    public function testAcceptInvitation(): void
    {
        $this->fixtures->createUser($email = 'some@buddy.works', $password = 'secret123');
        $this->fixtures->inviteUser($this->organizationId, 'some@buddy.works', $token = '04550fd6-47d1-491f-84d6-227d4a1a38e8');
        $this->loginUser($email, $password);

        $this->client->request('GET', $this->urlTo('organization_accept_invitation', ['token' => $token]));

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_overview', ['organization' => 'repman']))
        );
    }

    public function testRedirectOnAcceptInvitationWhenNotLogged(): void
    {
        $this->fixtures->createUser($email = 'some@buddy.works', $password = 'secret123');
        $this->fixtures->inviteUser($this->organizationId, 'some@buddy.works', $token = '04550fd6-47d1-491f-84d6-227d4a1a38e8');
        $this->logoutCurrentUser();

        $this->client->request('GET', $this->urlTo('organization_accept_invitation', ['token' => $token]));

        self::assertTrue($this->client->getResponse()->isRedirect($this->urlTo('app_login')));
    }

    public function testLogoutUserOnInvalidInvitation(): void
    {
        $this->fixtures->createUser($email = 'some@buddy.works', $password = 'secret123');
        $this->loginUser($email, $password);
        $this->client->request('GET', $this->urlTo('organization_accept_invitation', ['token' => '5d1dbd54-f231-48e6-9d24-9989362d812a']));

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('app_login'))
        );
        $this->client->followRedirects();
        $this->client->followRedirect();
        self::assertStringContainsString('Invitation not found or belongs to different user', (string) $this->client->getResponse()->getContent());
    }

    public function testRemoveMember(): void
    {
        $userId = $this->fixtures->addAcceptedMember($this->organizationId, $email = 'some@buddy.works');
        $this->client->request('GET', $this->urlTo('organization_members', ['organization' => 'repman']));

        self::assertStringContainsString($email, $this->lastResponseBody());

        $this->client->request('DELETE', $this->urlTo('organization_remove_member', ['organization' => 'repman', 'member' => $userId]));
        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_members', ['organization' => 'repman']))
        );

        $this->client->followRedirect();
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Member &quot;some@buddy.works&quot; has been removed from organization', (string) $this->client->getResponse()->getContent());
    }

    public function testPreventRemoveLastOwner(): void
    {
        $this->client->request('DELETE', $this->urlTo('organization_remove_member', ['organization' => 'repman', 'member' => $this->userId]));
        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_members', ['organization' => 'repman']))
        );

        $this->client->followRedirect();
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Member &quot;test@buddy.works&quot; cannot be removed. Organisation must have at least one owner.', (string) $this->client->getResponse()->getContent());
    }

    public function testChangeMemberRole(): void
    {
        $userId = $this->fixtures->addAcceptedMember($this->organizationId, $email = 'some@buddy.works', Member::ROLE_MEMBER);
        $this->client->request('GET', $this->urlTo('organization_change_member_role', ['organization' => 'repman', 'member' => $userId]));

        $this->client->submitForm('Change role', [
            'role' => Member::ROLE_OWNER,
        ]);

        self::assertTrue(
            $this->client->getResponse()->isRedirect($this->urlTo('organization_members', ['organization' => 'repman']))
        );

        $this->client->followRedirect();
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('Member &quot;some@buddy.works&quot; role has been successfully changed', (string) $this->client->getResponse()->getContent());
    }

    public function testPreventChangeLastOwnerRole(): void
    {
        $this->client->request('GET', $this->urlTo('organization_change_member_role', ['organization' => 'repman', 'member' => $this->userId]));

        $this->client->submitForm('Change role', [
            'role' => Member::ROLE_MEMBER,
        ]);

        self::assertTrue($this->client->getResponse()->isOk());
        self::assertStringContainsString('The role cannot be downgraded. Organisation must have at least one owner.', (string) $this->client->getResponse()->getContent());
    }
}
