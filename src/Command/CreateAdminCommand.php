<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Message\Admin\AddTechnicalEmail;
use Buddy\Repman\Message\Admin\ChangeConfig;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Service\Config;
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
    protected static $defaultName = 'repman:create:admin';

    private MessageBusInterface $bus;
    private Telemetry $telemetry;
    private Config $config;

    public function __construct(MessageBusInterface $bus, Telemetry $telemetry, Config $config)
    {
        $this->bus = $bus;
        $this->telemetry = $telemetry;
        $this->config = $config;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
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

        if (!$this->telemetry->isInstanceIdPresent()) {
            $this->askForTelemetry($input, $output);
        }

        if (!$this->config->isTechnicalEmailSet()) {
            $this->askForTechnicalEmail($input, $output, $email);
        }

        $this->bus->dispatch(new CreateUser(
            $id,
            $email,
            $plainPassword,
            Uuid::uuid4()->toString(),
            ['ROLE_ADMIN']
        ));

        $output->writeln(sprintf('Created admin user with id: %s', $id));

        return 0;
    }

    private function askForTelemetry(InputInterface $input, OutputInterface $output): void
    {
        $question = new ConfirmationQuestion(
            "Allow for sending anonymous usage statistic? [{$this->telemetry->docsUrl()}] (y/n)",
            true
        );

        if ($this->getHelper('question')->ask($input, $output, $question) === true) {
            $this->bus->dispatch(new ChangeConfig([
                Config::TELEMETRY => Config::TELEMETRY_ENABLED,
            ]));
        }

        $this->telemetry->generateInstanceId();
    }

    private function askForTechnicalEmail(InputInterface $input, OutputInterface $output, string $email): void
    {
        $question = new ConfirmationQuestion(
            'Allow for sending emails with software updates? (y/n)', true
        );

        if ($this->getHelper('question')->ask($input, $output, $question) === true) {
            $this->bus->dispatch(new ChangeConfig([Config::TECHNICAL_EMAIL => $email]));
            $this->bus->dispatch(new AddTechnicalEmail($email));
        }
    }
}
