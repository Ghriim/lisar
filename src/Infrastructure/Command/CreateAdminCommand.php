<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Domain\DTO\Input\Admin\CreateAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Admin\CreateAdminUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

use function is_string;
use function sprintf;

/**
 * How the first administrator comes to exist: nothing on the HTTP surface grants ROLE_ADMIN.
 */
#[AsCommand(
    name: 'lisar:user:create-admin',
    description: 'Create an administrator account',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(private readonly CreateAdminUseCase $useCase)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of the administrator');
        $this->addArgument('email', InputArgument::REQUIRED, 'E-mail, which is the sign-in identifier');
        $this->addOption(
            'password',
            'p',
            InputOption::VALUE_REQUIRED,
            'Password; prompted for, hidden, when this option is omitted',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = (string) $input->getArgument('username');
        $email = (string) $input->getArgument('email');
        $password = $input->getOption('password');

        if (false === is_string($password) || '' === $password) {
            // Asked for rather than passed on the command line, so it stays out of the shell
            // history and out of the process list.
            $question = (new Question('Password: '))->setHidden(true)->setHiddenFallback(false);
            $password = (string) $io->askQuestion($question);
        }

        try {
            $administrator = $this->useCase->execute(new CreateAdminDataInput($username, $email, $password));
        } catch (ValidationException $exception) {
            $io->error('The administrator was not created.');
            foreach ($exception->violations as $property => $errorCodes) {
                $io->writeln(sprintf('  <error>%s</error>: %s', $property, implode(', ', $errorCodes)));
            }

            return Command::FAILURE;
        }

        $io->success(sprintf('Administrator "%s" created (id %d).', $administrator->username, $administrator->id));

        return Command::SUCCESS;
    }
}
