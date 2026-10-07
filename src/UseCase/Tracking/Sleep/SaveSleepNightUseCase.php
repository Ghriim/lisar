<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Sleep;

use App\Domain\DataTransformer\TimeDataTransformer;
use App\Domain\DTO\DataModel\Tracking\SleepNightDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Sleep\SaveSleepNightDataInput;
use App\Domain\DTO\Output\Tracking\Sleep\SleepNightDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Tracking\Sleep\SleepNightOutputFactory;
use App\Domain\Gateway\Persister\Tracking\Sleep\SleepNightPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepNightProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Tracking\Sleep\SleepWindow;
use App\Domain\Validation\Validator\Tracking\Sleep\SaveSleepNightValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use LogicException;

/**
 * Noting the night one woke up from this morning. One use case, not a create and an update: the
 * night is keyed on its day, so the caller cannot know which of the two it is asking for and
 * must not have to.
 *
 * The day is never named by the caller. Whatever day a long-open page thinks it is, this writes
 * to the one in progress.
 */
final readonly class SaveSleepNightUseCase implements UseCaseInterface
{
    public function __construct(
        private SaveSleepNightValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private SleepNightProviderGateway $sleepNightProviderGateway,
        private SleepNightPersisterGateway $sleepNightPersisterGateway,
        private SleepNightOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, SaveSleepNightDataInput $input): SleepNightDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $today = $this->clock->today();

        $this->validator->validate($input, $today);

        $window = SleepWindow::forWakingDay(
            $today,
            $this->minutesOf($input->bedtime),
            $this->minutesOf($input->wakeUpTime),
        );

        $night = $this->sleepNightProviderGateway->findOneForOwnerAndDay($owner, $today);
        $isFirstNoteOfTheDay = null === $night;

        if (null === $night) {
            $night = new SleepNightDataModel();
            $night->owner = $owner;
            $night->day = $today;
        }

        // Both moments are built in the display timezone, so they are stored as the instants
        // they are and not as a wall clock that has lost its offset.
        $night->bedtimeAt = $this->clock->asStoredInstant($window->bedtimeAt);
        $night->wakeUpAt = $this->clock->asStoredInstant($window->wakeUpAt);
        // Assigned either way: clearing the face is how one unrates a night already noted.
        $night->moodRating = $input->moodRating;

        if (true === $isFirstNoteOfTheDay) {
            $this->sleepNightPersisterGateway->create($night);
        } else {
            $this->sleepNightPersisterGateway->update($night);
        }

        return $this->outputFactory->buildOne($night);
    }

    /** The validator has already refused anything malformed, so this cannot be null here. */
    private function minutesOf(string $time): int
    {
        $minutes = TimeDataTransformer::timeStringToMinutes($time);

        if (null === $minutes) {
            throw new LogicException(sprintf('The time "%s" reached the use case malformed.', $time));
        }

        return $minutes;
    }
}
