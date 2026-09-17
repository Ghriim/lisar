<?php

declare(strict_types=1);

namespace App\Infrastructure\HttpKernel\ArgumentResolver;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\DTO\Input\SensitiveDataInputInterface;
use App\Infrastructure\Exception\DataInputMappingException;
use JsonException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function is_array;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Turns the request into the DataInput a controller asked for with #[MapDataInput].
 *
 * A mapping failure is a contract breach by the caller: everything needed to diagnose it is
 * logged here, and the caller only ever sees the generic DataInputMappingException message.
 *
 * Targeted, not global: it runs only on the arguments that carry #[MapDataInput], never on the
 * use case a controller also receives.
 */
#[AsTargetedValueResolver]
final readonly class DataInputValueResolver implements ValueResolverInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<DataInputInterface>
     *
     * @throws DataInputMappingException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        $dataInputClass = $argument->getType();

        if (null === $dataInputClass || false === is_subclass_of($dataInputClass, DataInputInterface::class)) {
            // A programming error, not a runtime one: the attribute is on the wrong argument.
            throw new LogicException(sprintf('Argument "$%s" is annotated with #[MapDataInput] but is not typed as a %s.', $argument->getName(), DataInputInterface::class));
        }

        $payload = array_replace($request->query->all(), $this->getBodyData($request));

        try {
            $dataInput = $this->denormalizer->denormalize($payload, $dataInputClass, null, [
                // A query string carries strings and nothing else: "2" has to become the int a
                // DataInput declares, and "true" the bool. This is the whole reason one DataInput
                // can serve both GET ?a=1 and POST {json}.
                AbstractObjectNormalizer::ENABLE_TYPE_CONVERSION => true,
                'filter_bool' => true,
            ]);
        } catch (SerializerExceptionInterface $exception) {
            $this->logMappingFailure($request, $dataInputClass, $payload, $exception);

            throw new DataInputMappingException();
        }

        return [$dataInput];
    }

    /**
     * @return array<string, mixed>
     */
    private function getBodyData(Request $request): array
    {
        $content = $request->getContent();

        if ('' === $content) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return true === is_array($decoded) ? $decoded : [];
    }

    /**
     * @param class-string<DataInputInterface> $dataInputClass
     * @param array<string, mixed>             $payload
     */
    private function logMappingFailure(
        Request $request,
        string $dataInputClass,
        array $payload,
        SerializerExceptionInterface $exception,
    ): void {
        $context = [
            'route' => $request->attributes->get('_route'),
            'method' => $request->getMethod(),
            'content_type' => $request->headers->get('Content-Type'),
            'content_length' => $request->headers->get('Content-Length'),
            'data_input' => $dataInputClass,
            'payload_keys' => array_keys($payload),
            'reason' => $exception->getMessage(),
        ];

        if ($exception instanceof MissingConstructorArgumentsException) {
            $context['missing_fields'] = $exception->getMissingConstructorArguments();
        }

        if ($exception instanceof NotNormalizableValueException) {
            $context['invalid_path'] = $exception->getPath();
        }

        // Being logged is the default; not being logged is a declaration on the DataInput.
        if (false === is_subclass_of($dataInputClass, SensitiveDataInputInterface::class)) {
            $context['payload'] = $payload;
        }

        $this->logger->error('Unable to map the request onto a DataInput.', $context);
    }
}
