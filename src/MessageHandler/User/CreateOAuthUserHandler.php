<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateOAuthUser;
use Buddy\Repman\Message\User\GenerateApiToken;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User as SecurityUser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class CreateOAuthUserHandler implements MessageHandlerInterface
{
    private UserRepository $users;
    private EncoderFactoryInterface $encoderFactory;
    private MessageBusInterface $messageBus;

    public function __construct(UserRepository $users, EncoderFactoryInterface $encoderFactory, MessageBusInterface $messageBus)
    {
        $this->users = $users;
        $this->encoderFactory = $encoderFactory;
        $this->messageBus = $messageBus;
    }

    public function __invoke(CreateOAuthUser $message): void
    {
        $user = new User(
            $id = Uuid::uuid4(),
            $message->email(),
            $confirmToken = Uuid::uuid4()->toString(),
            ['ROLE_USER', 'ROLE_OAUTH_USER']
        );
        $user->setPassword($this->encoderFactory->getEncoder(SecurityUser::class)->encodePassword(Uuid::uuid4()->toString(), null));
        $user->confirmEmail($confirmToken);

        $this->users->add($user);

        $this->messageBus->dispatch(
            (new Envelope(
                new GenerateApiToken($id->toString(), 'default'))
            )->with(new DispatchAfterCurrentBusStamp())
        );
    }
}
