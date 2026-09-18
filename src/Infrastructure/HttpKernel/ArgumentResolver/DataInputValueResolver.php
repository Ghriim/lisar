<?php

declare(strict_types=1);

namespace App\Infrastructure\HttpKernel\ArgumentResolver;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\DTO\Input\SensitiveDataInputInterface;
use App\Infrastructure\Exception\DataInputMappingException;
use App\Infrastructure\HttpKernel\Attribute\NotTrimmed;
use JsonException;
use LogicException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function in_array;
use function is_array;
use function is_string;
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

        $payload = $this->trim(
            array_replace($request->query->all(), $this->getBodyData($request)),
            $dataInputClass,
        );

        try {
            // The format is declared, not left to default: the serializer only accepts a JSON
            // integer for a float property when it knows it is reading JSON, so without this a
            // DataInput taking a float refuses 72 and accepts 72.0 — and a browser sending 72.0
            // writes 72. Every decimal field in the API depends on this argument.
            $dataInput = $this->denormalizer->denormalize($payload, $dataInputClass, JsonEncoder::FORMAT, [
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
     * Surrounding whitespace is never meaningful in a payload: a title typed with a trailing
     * space is the same title, and a label of three spaces is a blank one. Trimming here, once,
     * is what lets every DataInput and every validator downstream ignore the question — and what
     * makes the value a validator accepts the very value the application stores.
     *
     * The one exception is a field marked #[NotTrimmed]: a secret is taken as sent.
     *
     * @param array<string, mixed>             $payload
     * @param class-string<DataInputInterface> $dataInputClass
     *
     * @return array<string, mixed>
     */
    private function trim(array $payload, string $dataInputClass): array
    {
        $rawFields = $this->getNotTrimmedFields($dataInputClass);

        foreach ($payload as $field => $value) {
            if (true === in_array($field, $rawFields, true)) {
                continue;
            }

            $payload[$field] = $this->trimDeep($value);
        }

        return $payload;
    }

    /** Walks into lists too: the tags of a task arrive as an array of strings. */
    private function trimDeep(mixed $value): mixed
    {
        if (true === is_string($value)) {
            return trim($value);
        }

        if (true === is_array($value)) {
            return array_map($this->trimDeep(...), $value);
        }

        return $value;
    }

    /**
     * @param class-string<DataInputInterface> $dataInputClass
     *
     * @return list<string>
     */
    private function getNotTrimmedFields(string $dataInputClass): array
    {
        $constructor = (new ReflectionClass($dataInputClass))->getConstructor();

        if (null === $constructor) {
            return [];
        }

        $fields = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ([] !== $parameter->getAttributes(NotTrimmed::class)) {
                $fields[] = $parameter->getName();
            }
        }

        return $fields;
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
