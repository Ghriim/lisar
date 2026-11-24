<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Domain\DTO\Input\Planning\CreatePlannedTasksDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Planning\CreatePlannedTasksUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

use function is_string;
use function sprintf;

/**
 * Makes the day's tasks of every recurring task entry, a few minutes past midnight where days are
 * counted (TRACKING_TIMEZONE, which the attribute cannot read: keep the two the same). Run by the
 * `worker` container through symfony/scheduler; a night it missed is replayed by hand with --day.
 */
#[AsCronTask('5 0 * * *', timezone: 'Europe/Paris')]
#[AsCommand(
    name: 'lisar:planning:create-tasks',
    description: 'Make the day\'s tasks of every recurring task planning entry',
)]
final class CreatePlannedTasksCommand extends Command
{
    public function __construct(private readonly CreatePlannedTasksUseCase $useCase)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('day', 'd', InputOption::VALUE_REQUIRED, 'The day to make the tasks of, "YYYY-MM-DD"; today when omitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $day = $input->getOption('day');

        try {
            $report = $this->useCase->execute(new CreatePlannedTasksDataInput(true === is_string($day) ? trim($day) : null));
        } catch (ValidationException $exception) {
            $io->error('No task was made.');
            foreach ($exception->violations as $property => $errorCodes) {
                $io->writeln(sprintf('  <error>%s</error>: %s', $property, implode(', ', $errorCodes)));
            }

            return Command::FAILURE;
        }

        $io->success(sprintf('%d planned task(s) made for %s.', $report->createdCount, $report->day));

        return Command::SUCCESS;
    }
}
