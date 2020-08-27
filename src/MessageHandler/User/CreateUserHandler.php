<?php

declare(strict_types=1);

namespace Buddy\Repman\MessageHandler\User;

use Buddy\Repman\Entity\User;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Message\User\GenerateApiToken;
use Buddy\Repman\Repository\UserRepository;
use Buddy\Repman\Security\Model\User as SecurityUser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

final class CreateUserHandler implements MessageHandlerInterface
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

    public function __invoke(CreateUser $message): void
    {
        $user = new User(
            Uuid::fromString($message->id()),
            $message->email(),
            $message->confirmToken(),
            $message->roles()
        );
        $user->setPassword($this->encoderFactory->getEncoder(SecurityUser::class)->encodePassword($message->plainPassword(), null));

        $this->users->add($user);

        $this->messageBus->dispatch(
            (new Envelope(
                new GenerateApiToken($message->id(), 'default'))
            )->with(new DispatchAfterCurrentBusStamp())
        );
    }
}
