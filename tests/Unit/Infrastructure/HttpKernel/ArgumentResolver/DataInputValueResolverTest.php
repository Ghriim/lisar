<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\HttpKernel\ArgumentResolver;

use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\DTO\Input\Task\CreateTaskDataInput;
use App\Domain\DTO\Input\Weight\SaveWeightDataInput;
use App\Infrastructure\Exception\DataInputMappingException;
use App\Infrastructure\HttpKernel\ArgumentResolver\DataInputValueResolver;
use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * The resolver is the one place that normalises what arrives from the transport, so this is the
 * one place that proves it.
 */
#[AllowMockObjectsWithoutExpectations]
final class DataInputValueResolverTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $denormalised = [];

    public function testItTrimsEveryStringItReceives(): void
    {
        $this->resolve(
            new Request(content: (string) json_encode([
                'title' => '  Acheter du lait  ',
                'description' => "  deux bouteilles\n",
            ])),
            CreateTaskDataInput::class,
        );

        self::assertSame('Acheter du lait', $this->denormalised['title']);
        self::assertSame('deux bouteilles', $this->denormalised['description']);
    }

    public function testItTrimsInsideLists(): void
    {
        $this->resolve(
            new Request(content: (string) json_encode(['title' => 'Courir', 'tags' => ['  sport ', 'urgent']])),
            CreateTaskDataInput::class,
        );

        self::assertSame(['sport', 'urgent'], $this->denormalised['tags']);
    }

    public function testItTrimsTheQueryStringToo(): void
    {
        $this->resolve(new Request(query: ['title' => '  Courir  ']), CreateTaskDataInput::class);

        self::assertSame('Courir', $this->denormalised['title']);
    }

    public function testItLeavesAnythingThatIsNotAStringAlone(): void
    {
        $this->resolve(
            new Request(content: (string) json_encode(['title' => 'Courir', 'priorityId' => 3])),
            CreateTaskDataInput::class,
        );

        self::assertSame(3, $this->denormalised['priorityId']);
    }

    /**
     * Credentials reach the application exactly as they were typed. Trimming a password would
     * silently forbid the ones that begin or end with a space, and trimming the identifier would
     * mean matching something other than what was sent.
     */
    public function testItLeavesEveryFieldMarkedNotTrimmedUntouched(): void
    {
        $this->resolve(
            new Request(content: (string) json_encode([
                'email' => '  alice@lisar.test  ',
                'password' => '  Corr3ct-Horse!  ',
            ])),
            LoginDataInput::class,
        );

        self::assertSame('  alice@lisar.test  ', $this->denormalised['email']);
        self::assertSame('  Corr3ct-Horse!  ', $this->denormalised['password']);
    }

    /**
     * A browser sends 72, not 72.0, for a weight of exactly seventy-two kilograms — and the
     * serializer only accepts a JSON integer for a float property when it is told it is reading
     * JSON. Without that, every decimal field in the API refuses whole numbers.
     *
     * Run against the real serializer, because the point being proved is its behaviour.
     */
    public function testItAcceptsAWholeNumberForADecimalField(): void
    {
        $resolver = new DataInputValueResolver($this->realDenormalizer(), new NullLogger());

        $resolved = $resolver->resolve(
            new Request(content: (string) json_encode(['weightInKilograms' => 72])),
            $this->metadataFor(SaveWeightDataInput::class),
        );

        self::assertInstanceOf(SaveWeightDataInput::class, $resolved[0]);
        self::assertSame(72.0, $resolved[0]->weightInKilograms);
    }

    /** And a decimal still arrives as one: the fix must not round the value to an int. */
    public function testItKeepsTheDecimalsOfADecimalField(): void
    {
        $resolver = new DataInputValueResolver($this->realDenormalizer(), new NullLogger());

        $resolved = $resolver->resolve(
            new Request(content: (string) json_encode(['weightInKilograms' => 71.85])),
            $this->metadataFor(SaveWeightDataInput::class),
        );

        self::assertInstanceOf(SaveWeightDataInput::class, $resolved[0]);
        self::assertSame(71.85, $resolved[0]->weightInKilograms);
    }

    public function testItRefusesAnArgumentThatIsNotADataInput(): void
    {
        $this->expectException(LogicException::class);

        $this->resolve(new Request(), stdClass::class);
    }

    public function testItTurnsAMappingFailureIntoItsOwnException(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willThrowException(
            new MissingConstructorArgumentsException('missing', 0, null, ['title']),
        );

        $resolver = new DataInputValueResolver($denormalizer, new NullLogger());

        $this->expectException(DataInputMappingException::class);

        $resolver->resolve(new Request(), $this->metadataFor(CreateTaskDataInput::class));
    }

    /**
     * @param class-string $type
     */
    private function resolve(Request $request, string $type): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer
            ->method('denormalize')
            ->willReturnCallback(function (mixed $data) use ($type): object {
                /* @var array<string, mixed> $data */
                $this->denormalised = $data;

                return (new ReflectionClass($type))->newInstanceWithoutConstructor();
            });

        $logger = $this->createMock(LoggerInterface::class);

        (new DataInputValueResolver($denormalizer, $logger))->resolve($request, $this->metadataFor($type));
    }

    /** The serializer the application actually runs, not a stand-in for it. */
    private function realDenormalizer(): DenormalizerInterface
    {
        return new Serializer([
            new ObjectNormalizer(propertyTypeExtractor: new ReflectionExtractor()),
        ]);
    }

    private function metadataFor(string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('input', $type, false, false, null);
    }
}
