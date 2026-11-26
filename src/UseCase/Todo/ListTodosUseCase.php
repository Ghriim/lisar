<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\ListTodosDataInput;
use App\Domain\DTO\Output\Todo\TodoDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TodoOutputFactory;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Validation\Validator\Todo\ListTodosValidator;
use App\UseCase\UseCaseInterface;

/**
 * The account's TODOs — the active ones, the archived ones, or all — « A faire » first, then
 * by title.
 */
final readonly class ListTodosUseCase implements UseCaseInterface
{
    public function __construct(
        private ListTodosValidator $validator,
        private TodoProviderGateway $todoProviderGateway,
        private TodoOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<TodoDataOutput>
     *
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, ListTodosDataInput $input): array
    {
        $this->validator->validate($currentUser, $input);

        return $this->outputFactory->buildMany($this->todoProviderGateway->findAllForOwner($currentUser, $input->status));
    }
}
