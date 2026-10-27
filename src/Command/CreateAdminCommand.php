<?php

declare(strict_types=1);

namespace Buddy\Repman\Command;

use Buddy\Repman\Message\Admin\AddTechnicalEmail;
use Buddy\Repman\Message\Admin\ChangeConfig;
use Buddy\Repman\Message\User\CreateUser;
use Buddy\Repman\Service\Config;
use Buddy\Repman\Service\Telemetry;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'repman:create:admin', description: 'Create admin user')]
final class CreateAdminCommand extends Command
{
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

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'e-mail used to log in')
            ->addArgument('password', InputArgument::OPTIONAL, 'plain password, if you don\'t provide it, you\'ll be asked for it')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $email */
        $email = $input->getArgument('email');
        /** @var string $plainPassword */
        $plainPassword = $input->getArgument('password') ?? $this->questionHelper()
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

        return Command::SUCCESS;
    }

    private function askForTelemetry(InputInterface $input, OutputInterface $output): void
    {
        $question = new ConfirmationQuestion(
            "Allow for sending anonymous usage statistic? [{$this->telemetry->docsUrl()}] (y/n)",
            true
        );

        if ($this->questionHelper()->ask($input, $output, $question) === true) {
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

        if ($this->questionHelper()->ask($input, $output, $question) === true) {
            $this->bus->dispatch(new ChangeConfig([Config::TECHNICAL_EMAIL => $email]));
            $this->bus->dispatch(new AddTechnicalEmail($email));
        }
    }

    private function questionHelper(): QuestionHelper
    {
        $helper = $this->getHelper('question');
        if (!$helper instanceof QuestionHelper) {
            throw new \LogicException('The question helper is not available');
        }

        return $helper;
    }
}
