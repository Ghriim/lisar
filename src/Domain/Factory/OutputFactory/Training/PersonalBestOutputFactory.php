<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\PersonalBestDataModel;
use App\Domain\DTO\Output\Training\MovementPersonalBestsDataOutput;
use App\Domain\DTO\Output\Training\PersonalBestBoardDataOutput;
use App\Domain\DTO\Output\Training\PersonalBestDataOutput;
use App\Domain\DTO\Output\Training\PersonalBestRecordDataOutput;
use App\Domain\Registry\Training\PersonalBestKindRegistry;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

use function array_search;
use function strcmp;
use function usort;

final readonly class PersonalBestOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * In the order records are listed in.
     *
     * @param iterable<PersonalBestDataModel> $rows
     *
     * @return list<PersonalBestDataOutput>
     */
    public function buildMany(iterable $rows): array
    {
        $outputs = [];
        foreach ($rows as $row) {
            $outputs[] = $this->buildOne($row);
        }
        usort($outputs, fn (PersonalBestDataOutput $a, PersonalBestDataOutput $b): int => $this->compare($a->kind, $a->tier, $b->kind, $b->tier));

        return $outputs;
    }

    public function buildOne(PersonalBestDataModel $row): PersonalBestDataOutput
    {
        $output = $this->mapper->map($row, PersonalBestDataOutput::class);
        $output->movementId = $row->movement?->id;
        $output->movementName = $row->movement?->name;
        $output->workoutId = (int) $row->workout->id;
        $output->setId = $row->set?->id;

        return $output;
    }

    /**
     * @param list<PersonalBestDataModel> $rows    every row of the account, oldest first
     * @param list<MovementDataModel>     $offered the movements on offer, listed even without records
     */
    public function buildBoard(array $rows, array $offered): PersonalBestBoardDataOutput
    {
        /** @var array<string, list<PersonalBestDataModel>> $sessionRecords */
        $sessionRecords = [];
        /** @var array<int, array<string, list<PersonalBestDataModel>>> $movementRecords */
        $movementRecords = [];
        /** @var array<int, MovementDataModel> $movements */
        $movements = [];

        foreach ($offered as $movement) {
            $movements[(int) $movement->id] = $movement;
            $movementRecords[(int) $movement->id] = [];
        }

        foreach ($rows as $row) {
            $key = $row->kind.'|'.($row->tier ?? '');
            if (null === $row->movement) {
                $sessionRecords[$key][] = $row;
                continue;
            }

            $movements[(int) $row->movement->id] = $row->movement;
            $movementRecords[(int) $row->movement->id][$key][] = $row;
        }

        $board = new PersonalBestBoardDataOutput();
        $board->sessions = $this->buildRecords($sessionRecords);

        foreach ($movements as $id => $movement) {
            $output = new MovementPersonalBestsDataOutput();
            $output->movementId = $id;
            $output->movementName = $movement->name;
            $output->movementFamilyId = (int) $movement->movementFamily->id;
            $output->movementFamilyName = $movement->movementFamily->name;
            $output->isOffered = $movement->isOffered();
            $output->records = $this->buildRecords($movementRecords[$id] ?? []);
            $board->movements[] = $output;
        }

        usort($board->movements, static fn (MovementPersonalBestsDataOutput $a, MovementPersonalBestsDataOutput $b): int => [$a->movementFamilyName, $a->movementName, $a->movementId] <=> [$b->movementFamilyName, $b->movementName, $b->movementId]);

        return $board;
    }

    /**
     * @param array<string, list<PersonalBestDataModel>> $progressions each record's rows, oldest first
     *
     * @return list<PersonalBestRecordDataOutput>
     */
    private function buildRecords(array $progressions): array
    {
        $records = [];
        foreach ($progressions as $rows) {
            $record = new PersonalBestRecordDataOutput();
            foreach ($rows as $row) {
                $record->progression[] = $this->buildOne($row);
            }
            $record->current = $record->progression[count($record->progression) - 1];
            $record->kind = $record->current->kind;
            $record->tier = $record->current->tier;
            $records[] = $record;
        }

        usort($records, fn (PersonalBestRecordDataOutput $a, PersonalBestRecordDataOutput $b): int => $this->compare($a->kind, $a->tier, $b->kind, $b->tier));

        return $records;
    }

    private function compare(string $kindA, ?float $tierA, string $kindB, ?float $tierB): int
    {
        $rankA = array_search($kindA, PersonalBestKindRegistry::ALL, true);
        $rankB = array_search($kindB, PersonalBestKindRegistry::ALL, true);

        return [$rankA, $tierA ?? 0.0] <=> [$rankB, $tierB ?? 0.0] ?: strcmp($kindA, $kindB);
    }
}
