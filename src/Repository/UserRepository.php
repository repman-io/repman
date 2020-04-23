<?php

declare(strict_types=1);

namespace Buddy\Repman\Repository;

use Buddy\Repman\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function emailExist(string $email): bool
    {
        return false !== $this->_em->getConnection()->fetchColumn('SELECT id FROM "user" WHERE email = :email', [
            ':email' => \mb_strtolower($email),
        ]);
    }

    public function getByEmail(string $email): User
    {
        $user = $this->findOneBy(['email' => \mb_strtolower($email)]);
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('User with email %s not found', \mb_strtolower($email)));
        }

        return $user;
    }

    public function getByResetPasswordToken(string $token): User
    {
        $user = $this->findOneBy(['resetPasswordToken' => $token]);
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('User with reset password token %s not found', $token));
        }

        return $user;
    }

    public function getByConfirmEmailToken(string $token): User
    {
        $user = $this->findOneBy(['emailConfirmToken' => $token]);
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('User with email confirm token %s not found', $token));
        }

        return $user;
    }

    public function getById(UuidInterface $id): User
    {
        $user = $this->find($id);
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('User with id %s not found', $id->toString()));
        }

        return $user;
    }

    public function add(User $user): void
    {
        $this->_em->persist($user);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function remove(UuidInterface $id): void
    {
        $this->_em->remove($this->getById($id));
    }
}
