<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Message\Admin\ChangeConfig;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Service\Telemetry;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateAdminCommand extends Command
{
    private MessageBusInterface $bus;
    private Telemetry $telemetry;

    public function __construct(MessageBusInterface $bus, Telemetry $telemetry)
    {
        $this->bus = $bus;
        $this->telemetry = $telemetry;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('repman:create:admin')
            ->setDescription('Create admin user')
            ->addArgument('email', InputArgument::REQUIRED, 'e-mail used to log in')
            ->addArgument('password', InputArgument::OPTIONAL, 'plain password, if you don\'t provide it, you\'ll be asked for it')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $email */
        $email = $input->getArgument('email');
        /** @var string $plainPassword */
        $plainPassword = $input->getArgument('password') ?? $this->getHelper('question')
            ->ask($input, $output, (new Question('User password:'))->setHidden(true));
        $id = Uuid::uuid4()->toString();

        $this->bus->dispatch(new CreateUser(
            $id,
            $email,
            $plainPassword,
            Uuid::uuid4()->toString(),
            ['ROLE_ADMIN']
        ));

        if (!$this->telemetry->isInstanceIdPresent()) {
            $question = new ConfirmationQuestion(
                "Allow for sending anonymous usage statistic? [{$this->telemetry->docsUrl()}] (y/n)",
                true
            );

            if ($this->getHelper('question')->ask($input, $output, $question) === true) {
                $question = new ConfirmationQuestion(
                    'Allow for sending emails with software updates? (y/n)',
                    true
                );
                $answer = $this
                    ->getHelper('question')
                    ->ask($input, $output, $question);

                $this->bus->dispatch(new ChangeConfig([
                    'telemetry' => 'enabled',
                    'technical_email' => $answer === true ? $email : '',
                ]));
            }

            $this->telemetry->generateInstanceId();
        }

        $output->writeln(sprintf('Created admin user with id: %s', $id));

        return 0;
    }
}
