<?php

declare(strict_types=1);

namespace Buddy\Repman\Security;

use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private Connection $connection;
    private UserRepository $userRepository;

    public function __construct(Connection $connection, UserRepository $userRepository)
    {
        $this->connection = $connection;
        $this->userRepository = $userRepository;
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $data = $this->getUserDataByEmail($identifier);

        if ($data === false) {
            throw new UserNotFoundException();
        }

        return $this->hydrateUser($data);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        $this->userRepository->upgradePassword($user, $newEncodedPassword);
    }

    public function emailExist(string $email): bool
    {
        return false !== $this->connection->fetchOne('SELECT id FROM "user" WHERE email = :email', [
                'email' => \mb_strtolower($email),
        ]);
    }

    /**
     * @return false|mixed[]
     */
    private function getUserDataByEmail(string $email)
    {
        return $this->connection->fetchAssociative(
            'SELECT
                id,
                email,
                password,
                status,
                email_confirmed_at,
                email_confirm_token,
                roles,
                email_scan_result,
                timezone
            FROM "user"
            WHERE email = :email',
            ['email' => \mb_strtolower($email)]
        );
    }

    /**
     * @param mixed[] $data
     */
    private function hydrateUser(array $data): User
    {
        $organizations = $this->connection->fetchAllAssociative('
            SELECT o.name, o.alias, om.role, o.has_anonymous_access FROM organization_member om
            JOIN organization o ON o.id = om.organization_id
            WHERE om.user_id = :userId ORDER BY o.name
        ', ['userId' => $data['id']]);

        return new User(
            $data['id'],
            $data['email'],
            $data['password'],
            $data['status'],
            $data['email_confirmed_at'] !== null,
            $data['email_confirm_token'],
            json_decode($data['roles'], true),
            array_map(fn (array $data) => new User\Organization($data['alias'], $data['name'], $data['role'], $data['has_anonymous_access']), $organizations),
            (bool) $data['email_scan_result'],
            $data['timezone'],
        );
    }
}
