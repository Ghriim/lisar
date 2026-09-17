# Backend architecture blueprint

This document describes a backend architecture **as a reusable template**, extracted from a
production PHP/Symfony microservice. It is written so that an engineer (or an AI agent) who has
never seen the original codebase can build a **different application, with a completely different
functional purpose, but with an identical structure** — so that developers familiar with the
original feel instantly at home.

Nothing here is tied to the original business domain. All examples use a neutral placeholder
domain (`Account` / `Subscription` / `Plan`) — replace it with yours.

> **How to use this document.** Read §2 to §5 to understand the layering. Use §6 as a copy-paste
> catalogue of building blocks. Use §13 as the checklist when adding a feature. §14 lists the
> points where the source codebase is inconsistent and the ruling this blueprint adopts.

---

## 1. Stack

| Concern | Choice |
| --- | --- |
| Language | PHP 8.4 (`declare(strict_types=1)` everywhere) |
| Framework | Symfony 8.1 (framework-bundle, no full-stack `symfony/symfony`) |
| Persistence | MySQL 8 via Doctrine ORM 3 + Doctrine Migrations |
| HTTP in | Symfony controllers, JSON only, documented with `nelmio/api-doc-bundle` (OpenAPI attributes) |
| HTTP out | `symfony/http-client` |
| Mapping data model → output DTO | `symfony/object-mapper` (`#[Map]` attributes), always behind an OutputFactory |
| Input validation | `symfony/validator` (attributes on DataInputs) + hand-written domain constraints |
| Event contracts | JSON Schema (`opis/json-schema`) |
| Fixtures | `doctrine/doctrine-fixtures-bundle` — plain PHP fixture classes, no YAML DSL |
| Tests | PHPUnit 13, two suites, `dama/doctrine-test-bundle` for isolation |
| Static analysis | PHPStan level 8 on `../src` (+ Symfony extension) |
| Style | PHP-CS-Fixer: `@PSR12` + `@Symfony` + `declare_strict_types` |
| Runtime | Docker Compose; every command goes through `make` |
| Observability | OpenTelemetry auto-instrumentation + Monolog |

Everything runs in containers. The host never needs PHP, Composer or MySQL.

---

## 2. The one rule: layers and their direction

```
                 ┌──────────────────────────────────────────┐
   HTTP / CLI ──▶│  Infrastructure/Controller  Infrastructure/Command
                 └───────────────────┬──────────────────────┘
                                     │  (only these two may import UseCase)
                                     ▼
                 ┌──────────────────────────────────────────┐
                 │                 UseCase/                 │
                 └───────────────────┬──────────────────────┘
                                     │  depends on Domain contracts only
                                     ▼
                 ┌──────────────────────────────────────────┐
                 │  Domain/  (DTO, DataModel, Gateway, Rules)│
                 └───────────────────▲──────────────────────┘
                                     │  implements Gateway interfaces
                 ┌───────────────────┴──────────────────────┐
                 │  Infrastructure/ (Repository, Persister, │
                 │  HttpClient, EventHandler, Serializer…)  │
                 └──────────────────────────────────────────┘
```

**Dependency matrix** (rows import columns):

| | `Domain` | `UseCase` | `Infrastructure` | Vendor |
| --- | --- | --- | --- | --- |
| `Domain` | yes | **never** | **never** (see exceptions) | only the 4 whitelisted Doctrine/`\Exception` symbols |
| `UseCase` | yes | yes | **exceptions only** (`Infrastructure\Exception\*`), plus tagged-service finders | yes |
| `Infrastructure` | yes — but only `Repository`/`Persister`/`EventHandler`/`HttpClient` classes touch `Domain\DTO\DataModel` and `Domain\Gateway` | only `Controller` and `Command` | yes | yes |

`src/Fixtures/` sits **outside** the three layers: dev- and test-only seed data. It may import
`Domain` and nothing else, and no application code ever references it.

### 2.1 Domain purity

`Domain/` contains **no framework**. Exactly four vendor symbols are allowed, and only in
`Domain/DTO/DataModel/`:

- `Doctrine\ORM\Mapping as ORM`
- `Doctrine\DBAL\Types\Types`
- `Doctrine\Common\Collections\Collection` and `...\ArrayCollection` (Doctrine forces to-many
  associations to be typed as `Collection<…>`; there is no array-based alternative)

Plus `\Exception`, allowed only in `Domain/Exception/`.

**`symfony/validator` constraint attributes on DataInputs are a deliberate, accepted exception.**
`#[Assert\NotBlank]`, `#[Assert\Range]` and friends live on the DataInputs in `Domain/DTO/Input/`
and that is the ruling, not a compromise waiting to be undone. The reasoning:

- a shape rule declared next to the property it guards cannot drift away from it, which is
  exactly what happens to a hand-written `Constraint` mirroring a DTO;
- the constraints double as the OpenAPI schema `nelmio/api-doc-bundle` publishes — one
  declaration, two consumers;
- they are inert metadata: nothing is executed until a validator asks, so the DTO stays a value
  object;
- the rules that actually carry business meaning (uniqueness, cross-model coherence, state
  transitions) are *not* attributes — they are `Constraint` classes (§6.8). The split is
  "shape versus meaning", and it is easy to apply in review.

So the whitelist of vendor symbols allowed in `Domain/` is the four Doctrine ones, `\Exception`,
and `Symfony\Component\Validator\Constraints` in `Domain/DTO/Input/`. Nothing else.

### 2.2 Why the Gateway pattern

Doctrine never leaks past `Infrastructure/`. A UseCase asks for `AccountProviderGateway`, not for
`AccountRepository` and never for `EntityManagerInterface`. This buys two things: the storage
engine is swappable, and unit tests mock one small interface instead of Doctrine.

---

## 3. Repository layout

```
.
├── .deploy/                  # Terraform/HCL per environment (infra owned, not app code)
├── .git-hooks/pre-commit     # cs-fix + stan + unit tests, aborts on unstaged reformat
├── appconfig/                # runtime config shipped with the image
├── bin/                      # console, phpunit
├── config/                   # Symfony config (see §11)
├── docker/                   # container-side assets (php ini, sidecar components…)
├── docs/                     # the living documentation: conventions, architecture,
│                             #   product specs, env vars, generated schema dumps
├── frontend/                 # the two React+TS+Vite apps (website, admin) — not covered here
├── fixtures/                 # NON-database assets only, shared by dev and tests
│   ├── <Integration>Mock/    #   recorded third-party payloads (.json)
│   └── <Bus>/Events/         #   sample inbound events (.json)
│                             #   DB seed data lives in src/Fixtures/
├── migrations/               # Doctrine migrations, Version<UTC timestamp>.php
├── nginx/                    # vhost
├── schemas/                  # JSON Schemas of the contracts we consume/expose
│   └── import/               #   inbound event schemas, one file per event version
├── src/                      # see §4
├── tests/                    # mirrors src/, see §9
├── CLAUDE.md                 # agent-facing summary of this document
├── Makefile                  # the ONLY supported entry point for every command
├── docker-compose.yml        # app, nginx, mysql, app-test, mysql-test, sidecars
├── phpstan.neon              # level 8, paths: src
├── phpunit.xml               # two suites: unit, integration
└── .php-cs-fixer.dist.php
```

Two conventions worth copying verbatim:

- **`docs/` is the source of truth**, not the README — conventions, architecture, database
  schema and product specs alike. It is short, imperative, reviewed like code, and written as
  the work happens rather than after the fact. Split it `docs/dev/` (how the code is built)
  and `docs/product/` (what each feature does).
- **`CLAUDE.md` at the root** is a condensed, agent-readable version of the same rules plus the
  command list. Keep it in sync with `docs/`; when they disagree, `docs/` wins.

### 3.1 Commands: everything goes through `make`

Nothing is run from the host. Every target `docker compose exec`s into the right container, so
`../bin/console`, `bin/phpunit` and `vendor/bin/*` are never invoked directly.

The one exception is the front ends: they are Node applications, not PHP, and their dev
servers run on the host. They still go through `make`, so the entry point stays the same.

**Target names are kebab-case, one name per action, no aliases and no synonyms.** A target that
exists under two spellings is how `reset_db` and `reset-db` ended up meaning two different things
in the source codebase — one of them wrong. The canonical set:

| Target | Does |
| --- | --- |
| `make setup` | one-off: local files + `git config core.hooksPath .git-hooks/` |
| `make build` | full rebuild, all containers up, migrations on the dev DB |
| `make start` | `build` + `reset-db` + `load-fixtures` + the projection-building command |
| `make start-website` / `make start-admin` | start a front end's dev server and open it |
| `make stop` | stop the containers |
| `make reset-db` / `make reset-test-db` | drop + create + migrate the dev / test DB |
| `make load-fixtures` | `doctrine:fixtures:load` on the dev DB |
| `make cs-fix` | PHP-CS-Fixer on `../src` |
| `make stan` | PHPStan level 8 on `../src` |
| `make test` | full suite (migrations first, then unit, then integration) |
| `make test-unit` / `make test-integration` | one suite, with `file=`, `class=`, `debug=true`, `coverage=true` |
| `make pre-commit` | `cs-fix` + `stan` + `test-unit`, same sequence as the git hook |
| `make db-connect` | interactive MySQL shell on the dev DB |

Two rules that keep this honest:

- **The `README`, `../CLAUDE.md` and `.git-hooks/pre-commit` cite only targets that exist.** In the
  source codebase the README and the agent-facing doc both told people to run `make cs-fix` and
  `make stan` while the Makefile only defined `php-cs-fixer` and `phpstan` — two commands that
  simply failed, for months. Add a CI step that greps every `make <target>` mentioned in the docs
  and the hook against `make -qp`'s target list.
- The test containers (`app-test`, `mysql-test`) are separate from the dev ones, with their own
  database, so a test run can never touch the working data.

---

## 4. `../src` map

```
src/
├── Kernel.php
│
├── Domain/                              # pure business, no framework
│   ├── DTO/                             # the three data shapes: in, out, persisted
│   │   ├── Input/
│   │   │   ├── DataInputInterface.php           # marker
│   │   │   ├── SensitiveDataInputInterface.php  # marker: never log this payload
│   │   │   └── <SubDomain>/<Verb><Thing>DataInput.php
│   │   ├── Output/
│   │   │   ├── <SubDomain>/<Thing>DataOutput.php
│   │   │   └── SimpleReportDataOutput.php       # generic {message} envelope
│   │   └── DataModel/                           # Doctrine-mapped, public props, no accessors
│   │       ├── DataModelInterface.php           # marker
│   │       └── <Noun>DataModel.php
│   ├── Gateway/
│   │   ├── Provider/<Noun>ProviderGateway.php   # read contracts
│   │   └── Persister/<Noun>PersisterGateway.php # write contracts
│   ├── Factory/
│   │   ├── DataModelFactory/<Noun>DataModelFactory.php  # data model ← data model/DTO
│   │   └── OutputFactory/<Thing>OutputFactory.php       # DataOutput ← data model
│   ├── Registry/<SubDomain>/<Name>Registry.php    # interfaces holding constants
│   ├── Validation/
│   │   ├── Validator/<SubDomain>/<Verb><Thing>Validator.php
│   │   └── Constraint/<SubDomain>/<Rule>Constraint.php
│   ├── DataTransformer/<Name>DataTransformer.php  # PURE formatters, usable by DataOutputs
│   ├── Exception/ValidationException.php
│   └── <SubDomain>/<DomainService>.php            # stateless domain services
│
├── UseCase/                              # application services, one per user intent
│   ├── UseCaseInterface.php              # marker
│   └── <SubDomain>/<Verb><Thing>UseCase.php
│
├── Infrastructure/                       # everything that talks to the outside world
│   ├── Controller/                        # one folder per audience, never loose at the root
│   │   ├── User/<Aggregate>Controller.php         # what the website calls
│   │   └── Admin/Admin<Aggregate>Controller.php   # back-office surface, ROLE_ADMIN
│   ├── Command/<Verb><Thing>Command.php
│   │   └── Dev/                          # developer-only commands, never run in prod
│   ├── Repository/<Noun>Repository.php             # implements a ProviderGateway
│   ├── Persister/
│   │   ├── AbstractBaseMysqlPersister.php
│   │   └── <Noun>Persister.php                     # implements a PersisterGateway
│   ├── HttpClient/
│   │   ├── <Integration>/                         # one namespace per outbound integration family
│   │   │   ├── <Family>HttpClientInterface.php    # tagged contract
│   │   │   ├── Abstract<Family>HttpClient.php     # shared request + logging
│   │   │   └── <Vendor>HttpClient.php             # one per third party
│   │   └── <Simple>HttpClient.php                 # one-off clients (Slack, …)
│   ├── DTO/<Integration>/<Thing>DTO.php           # third-party payload shapes
│   ├── Factory/<Integration>/<Vendor>DTOFactory.php
│   ├── EventHandler/
│   │   ├── <Bus>EventHandlerInterface.php         # tagged contract
│   │   ├── <Bus>EventHandlerFinder.php            # name → handler
│   │   └── <Topic>/<Event>EventHandler.php
│   ├── Serializer/<Thing>Denormalizer.php
│   ├── Doctrine/Type/<Name>Type.php               # custom DBAL types
│   ├── Validation/<Name>Validator.php             # schema validation, I/O bound
│   ├── DataTransformer/<Name>DataTransformer.php  # vendor parsing / sanitising, never used by Domain
│   ├── Exception/                                 # DataModelNotFound, DataInputMapping, …
│   └── HttpKernel/
│       ├── Attribute/MapDataInput.php
│       ├── ArgumentResolver/DataInputValueResolver.php
│       ├── DependencyInjection/<Name>Resolver.php # tagged-service locators
│       └── EventListener/ExceptionListener.php
│
└── Fixtures/<Noun>Fixtures.php           # Doctrine seed data, dev/test only (§10)
```

---

## 5. Naming catalogue

Suffixes are **mandatory** and carry the layer contract. Domain services are the only unsuffixed
classes.

**The `DataModel` suffix marks the persisted class itself, and nothing else.** Everything built
*around* a data model is named after the bare concept: `SubscriptionDataModel` is served by
`SubscriptionProviderGateway`, `SubscriptionRepository`, `SubscriptionPersister`,
`SubscriptionDataOutput` and `CreateSubscriptionUseCase` — never
`SubscriptionDataModelRepository`. Sub-domain folders and namespaces likewise use the bare noun
(`UseCase/Subscription/`, `Domain/DTO/Input/Subscription/`).

| Kind | Pattern | Example |
| --- | --- | --- |
| Persisted data model | `<Noun>DataModel`, in `Domain/DTO/DataModel/` | `SubscriptionDataModel` |
| Read gateway (interface) | `<Noun>ProviderGateway` | `SubscriptionProviderGateway` |
| Write gateway (interface) | `<Noun>PersisterGateway` | `SubscriptionPersisterGateway` |
| Read implementation | `<Noun>Repository` | `SubscriptionRepository` |
| Write implementation | `<Noun>Persister` | `SubscriptionPersister` |
| Repository query method | `findOneFor<Context>` / `findFor<Context>` / `findOneBy<Criteria>` | `findOneForSubscriptionDetails` |
| Input DTO | `<Verb><Thing>DataInput` | `CreateSubscriptionDataInput` |
| Output DTO | `<Thing>DataOutput` | `SubscriptionDataOutput` |
| Third-party DTO | `<Thing>DTO` | `PlanDTO` |
| Use case | `<Verb><Thing>UseCase` | `CancelSubscriptionUseCase` |
| Validator | `<Verb><Thing>Validator` | `CreateSubscriptionValidator` |
| Domain constraint | `<Rule>Constraint` | `NameAvailableConstraint` |
| Data model factory | `<Noun>DataModelFactory` | `SubscriptionDataModelFactory` |
| Output factory | `<Thing>OutputFactory` | `SubscriptionOutputFactory` |
| Constants holder | `<Name>Registry` (an **interface**) | `SubscriptionStatusRegistry` |
| Domain service | `<Concept>` — **no suffix**, in `Domain/<Aggregate>/` | `SubscriptionEligibility` |
| Controller | `<Aggregate>Controller` | `SubscriptionController` |
| Console command | `<Verb><Thing>Command` | `ExpireSubscriptionsCommand` |
| Outbound client | `<Vendor>HttpClient` | `StripeHttpClient` |
| Inbound handler | `<Event>EventHandler` | `AccountCreatedEventHandler` |
| Value formatter | `<Name>DataTransformer` | `DateDataTransformer` |
| Tagged-service locator | `<Name>Resolver` / `<Name>Finder` | `PaymentHttpClientResolver` |

**Verb vocabulary for use cases** — stay inside this list so intent is greppable:
`Get…` (one, by identity), `List…` (many), `Create…`, `Register…` (create through a public,
unauthenticated sign-up — as opposed to `Create…`, which an authenticated actor performs),
`Update…`, `Delete…`, `Activate…`, `Deactivate…`, `Refresh…` (re-pull from a third party, or renew a short-lived artefact of our own, such as a
session),
`Complete…` / `Reopen…` (close and unclose something the person ticks off),
`Import…` (ingest a file/feed), `Build…` (derive and persist a projection), `Process…` (consume an
event), `Fetch…` (call a third party and persist the result).

**Other naming rules:**

- Booleans are `is*` (state) or `has*` (possession): `isActive`, `hasTrial`. An idiomatic
  third-person verb (`tracksUsage`) is acceptable when it reads better, but `is`/`has` is the
  default and any deviation must justify itself.
- Console command names: `<service-prefix>:<group>:<action>`, e.g. `billing:subscription:expire`.
  Keep **one** prefix for the whole service, singular.
- Route paths are kebab-case plural nouns: `/subscriptions`, `/accounts/{id}/subscriptions`.
- Error codes are snake_case: `create_subscription_invalid`, `name_already_used`.

---

## 6. Building blocks

### 6.1 DataModel

A **data model** is a persisted shape: a Doctrine-mapped class living in
`Domain/DTO/DataModel/<Noun>DataModel.php`. It sits next to `DataInput` and `DataOutput` under
`Domain/DTO/` on purpose — these are the three data shapes the application manipulates, one per
direction: what comes in, what goes out, what is stored. Nothing else in the codebase is called an
"entity".

Data models are the only non-`final` classes (Doctrine needs to proxy them). Public properties,
**no getters or setters**. They may carry small, pure query helpers over their own associations —
never persistence logic.

```php
<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'subscription'), ORM\Entity]
class SubscriptionDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    public string $reference;

    // Percentages and money are float columns typed DECIMAL — never decimal-as-string.
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => 0.0])]
    public float $discountRate = 0.0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isActive = false;

    #[ORM\ManyToOne(targetEntity: AccountDataModel::class, inversedBy: 'subscriptions')]
    public AccountDataModel $account;

    /** @var Collection<int, InvoiceDataModel> */
    #[ORM\OneToMany(targetEntity: InvoiceDataModel::class, mappedBy: 'subscription', cascade: ['remove'])]
    public Collection $invoices;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
    }
}
```

Rules:

- Implement the empty marker `DataModelInterface` (it types the abstract persister's generic).
- **Every data model declares `createdAt` and `updatedAt`**, nullable `?\DateTimeImmutable = null` —
  projections, cache tables and append-only logs included, with no exception to remember. They
  are stamped by the abstract persister; never assign them by hand.
- Decimal columns are `float` + `Types::DECIMAL` with explicit `precision`/`scale`. Do not use
  decimal-as-string.
- No data model lifecycle callbacks; side effects live in the persister.
- Inline `//` comments on non-obvious columns (units, business meaning, why nullable) are
  encouraged and reviewed.

### 6.2 Gateways

One interface per data model per direction. They are the **only** contract a use case sees.

```php
// src/Domain/Gateway/Provider/SubscriptionProviderGateway.php
interface SubscriptionProviderGateway
{
    public function findOneById(int $id): ?SubscriptionDataModel;

    /** @return list<SubscriptionDataModel> */
    public function findAllForAccount(AccountDataModel $account, ?bool $isActive = null): array;
}

// src/Domain/Gateway/Persister/SubscriptionPersisterGateway.php
interface SubscriptionPersisterGateway
{
    public function create(SubscriptionDataModel $subscription): SubscriptionDataModel;

    public function update(SubscriptionDataModel $subscription): SubscriptionDataModel;

    public function delete(SubscriptionDataModel $subscription): void;
}
```

The **one-to-one binding is auto-wired**: because exactly one class implements the interface,
Symfony resolves it without a `services.yaml` alias. Two implementations of the same gateway is a
design error — split the gateway instead.

### 6.3 Repository (read side)

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\DTO\DataModel\AccountDataModel;
use App\Domain\DTO\DataModel\SubscriptionDataModel;
use App\Domain\Gateway\Provider\SubscriptionProviderGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionDataModel>
 */
final class SubscriptionRepository extends ServiceEntityRepository implements SubscriptionProviderGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionDataModel::class);
    }

    public function findOneById(int $id): ?SubscriptionDataModel
    {
        return $this->createQueryBuilder('subscription')
            ->innerJoin('subscription.account', 'account')
            ->addSelect('account')
            ->andWhere('subscription.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<SubscriptionDataModel> */
    public function findAllForAccount(AccountDataModel $account, ?bool $isActive = null): array
    {
        $qb = $this->createQueryBuilder('subscription')
            ->andWhere('subscription.account = :account')
            ->setParameter('account', $account)
            ->orderBy('subscription.reference', 'ASC');

        if (null !== $isActive) {
            $qb->andWhere('subscription.isActive = :isActive')
                ->setParameter('isActive', $isActive);
        }

        return $qb->getQuery()->getResult();
    }
}
```

Rules:

- **Never** call Doctrine's generic `find`, `findBy`, `findOneBy`, `findAll` from outside. Write a
  context-named method whose name says what the call site needs.
- **Never rely on lazy loading.** Every association a caller will read is `innerJoin`/`leftJoin`
  + `addSelect`ed in the query that loads it. This is the single biggest performance rule.
- Query builder aliases are the data model name in camelCase (`subscription`, `account`).
- A repository implements exactly one gateway, and is `final`.

### 6.4 Persister (write side)

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\DataModelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * @template T of DataModelInterface
 */
abstract class AbstractBaseMysqlPersister
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ClockInterface $clock,
    ) {
    }

    /**
     * @param T $dataModel
     *
     * @return T
     */
    protected function persistAndStampCreate(DataModelInterface $dataModel, bool $flush = true): DataModelInterface
    {
        $now = \DateTimeImmutable::createFromInterface($this->clock->now());

        if (property_exists($dataModel, 'createdAt')) {
            $dataModel->createdAt = $now;
        }
        if (property_exists($dataModel, 'updatedAt')) {
            $dataModel->updatedAt = $now;
        }

        $this->entityManager->persist($dataModel);

        if (true === $flush) {
            $this->entityManager->flush();
        }

        return $dataModel;
    }

    /** @param T $dataModel @return T */
    protected function persistAndStampUpdate(DataModelInterface $dataModel, bool $flush = true): DataModelInterface { /* … */ }

    /** @param T $dataModel */
    protected function persistDelete(DataModelInterface $dataModel, bool $flush = true): void { /* … */ }
}
```

```php
/**
 * @extends AbstractBaseMysqlPersister<SubscriptionDataModel>
 */
final class SubscriptionPersister extends AbstractBaseMysqlPersister implements SubscriptionPersisterGateway
{
    public function create(SubscriptionDataModel $subscription): SubscriptionDataModel
    {
        return $this->persistAndStampCreate($subscription);
    }

    public function update(SubscriptionDataModel $subscription): SubscriptionDataModel
    {
        return $this->persistAndStampUpdate($subscription);
    }

    /** @param SubscriptionDataModel[] $subscriptions */
    public function updateMany(array $subscriptions): void
    {
        foreach ($subscriptions as $subscription) {
            $this->persistAndStampUpdate($subscription, flush: false);
        }

        $this->entityManager->flush();
    }

    public function delete(SubscriptionDataModel $subscription): void
    {
        $this->persistDelete($subscription);
    }
}
```

Rules:

- Time comes from `Psr\Clock\ClockInterface`, never `new \DateTimeImmutable()`. That is what makes
  timestamps assertable in tests.
- Batch methods take `flush: false` per item and flush once at the end.
- **All post-create/update/delete side effects live in the persister** (cache invalidation,
  denormalised counters, outbound notifications). A use case that persists gets them for free.
- Persisters do not read. If a write needs a lookup, the use case does the lookup through a
  provider gateway and hands the data model over.
- **Every persister implements a `PersisterGateway`, without exception** — including those only
  Infrastructure writes to (API-call logs, vendor raw payloads, rebuilt projections). One rule,
  no judgement call in review, and the day a use case needs that write it already has a contract.
- A persister is injected through its gateway interface, never as a concrete class.

### 6.5 DataInput

Immutable, `final readonly`, constructor-promoted public properties, Symfony validator attributes
inline. Implements `DataInputInterface`, or `SensitiveDataInputInterface` when the raw payload
must never reach the logs (credentials, tokens, personal data).

```php
final readonly class CreateSubscriptionDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank]
        public string $accountReference,

        #[Assert\NotBlank]
        public string $planCode,

        // Upper bound imposed by the DECIMAL(5,2) column, not a business rule.
        #[Assert\Range(min: 0, max: 999.99)]
        public ?float $discountRate = null,

        public ?string $comments = null,
    ) {
    }
}
```

Rules:

- One DataInput per use case that takes structured input. Do not share a DataInput between a
  create and an update — their rules diverge immediately.
- Attribute constraints cover **shape** (required, type, range, format). Rules that need the
  database or other data models are `Constraint` classes (§6.8).
- **Every `#[Assert\…]` carries a snake_case error code as its `message`** (`username_too_short`,
  not the component's English sentence). The violation payload is a contract two front ends read:
  shape violations and domain constraints must speak the same vocabulary, and a client must never
  have to parse prose to know which rule it broke.
- Document with a comment any bound that comes from the schema rather than the business.

### 6.6 DataOutput

Plain `final` classes with public, non-promoted properties, populated by `symfony/object-mapper`
**through an OutputFactory** (§6.9). They are serialised directly by `JsonResponse`, so **the
class is the API contract**.

```php
final class SubscriptionDataOutput
{
    public int $id;

    public string $reference;

    public float $discountRate;

    public bool $isActive;

    #[Map(source: 'account.id')]
    public int $accountId;

    #[Map(source: 'account.name')]
    public string $accountName;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $createdAt = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $updatedAt = null;
}
```

Rules:

- Not `readonly` — the mapper writes into them.
- `#[Map(source: 'a.b.c')]` flattens associations; `#[Map(transform: […])]` formats scalars.
  Both keep the mapping declarative and next to the field it produces.
- **A dotted `source` only works on a non-nullable association.** The property accessor
  cannot walk through a null and raises a 500 rather than leaving the field null, so a
  nullable association is assigned in the OutputFactory instead. `#[Map(if: false)]` marks
  every field the factory fills in, which documents at a glance what the mapper does not own.
- Dates are **strings** in outputs, formatted by the single shared `DateDataTransformer`, so the
  whole API uses one date format: **ISO 8601 with offset**, `Y-m-d\TH:i:sP`
  (`"2026-09-17T14:32:05+02:00"`). No endpoint invents its own.
- The transformer a DataOutput references always comes from `Domain/DataTransformer/` (§6.18) —
  a Domain DTO never imports `Infrastructure`.
- Anything the mapper cannot express declaratively (conditional fields, sub-object assembly) goes
  in the **OutputFactory** (§6.9), never in the use case and never in the controller.
- `SimpleReportDataOutput` is the generic `{"message": "..."}` envelope for endpoints that only
  acknowledge.
- `PaginatedListDataOutput<TItem>` is the generic envelope **every** paginated list answers with:
  `items`, `total` (matching rows, ignoring pagination), `page`, `perPage`. No endpoint invents
  its own pagination shape, so a table on either front end is written once. The list's DataInput
  carries `page` and `perPage` with a hard cap, and exposes `getOffset()`.

### 6.7 Registry

Constants attached to a DTO or a sub-domain live in an **interface** under
`Domain/Registry/<SubDomain>/`. An interface (not a final class with constants, **not a backed
enum**) so it can never be instantiated and constants can be referenced without importing
behaviour.

```php
interface SubscriptionStatusRegistry
{
    public const string PENDING = 'pending';
    public const string ACTIVE = 'active';
    public const string CANCELLED = 'cancelled';
}
```

Always type the constant (`public const string`, `public const int`).

**Why not a backed enum**, given PHP 8.4 has them. The values a service like this holds are
overwhelmingly *someone else's* strings — versioned event names, vendor status codes, column
values that predate the current schema. A backed enum makes an unexpected value a fatal
`ValueError` at the deserialisation boundary, which turns a third party shipping a new status
into an outage; a Registry constant makes it a value that flows through and is handled where the
business decides. It also keeps Doctrine mappings, JSON payloads and third-party contracts free of
conversion layers. Registries stay `string`/`int` typed constants, everywhere, including for
values we do own — one rule beats a per-case judgement about who owns the vocabulary.

### 6.8 Validators and Constraints

A **Validator** is the validation entry point for exactly one use case. It is `final readonly`,
has no shared interface (its `validate()` signature is typed to its DataInput), returns `void`,
and throws a single `ValidationException` carrying **every** violation.

```php
/**
 * @extends AbstractBaseValidator<CreateSubscriptionDataInput>
 */
final readonly class CreateSubscriptionValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'create_subscription_invalid';

    /**
     * @param SubscriptionDataModel[] $existingSubscriptions
     *
     * @throws ValidationException
     */
    public function validate(CreateSubscriptionDataInput $input, array $existingSubscriptions): void
    {
        $violations = $this->getViolations($input);
        $violations = ReferenceAvailableConstraint::validate($input->reference, $existingSubscriptions, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
```

`AbstractBaseValidator` wraps `symfony/validator` and turns its violation list into
`array<string, list<string>>` (property path → error codes):

```php
/**
 * @template T of DataInputInterface
 */
abstract readonly class AbstractBaseValidator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @param T $input
     *
     * @return array<string, list<string>>
     */
    protected function getViolations(DataInputInterface $input): array
    {
        $violations = [];
        foreach ($this->validator->validate($input) as $violation) {
            $violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
        }

        return $violations;
    }
}
```

A **Constraint** is one reusable rule. It is `final readonly` with a **static** `validate()` that
takes the value, whatever context it needs, and the accumulated `$violations` array, and returns
the array back. Static and pure: no dependencies, no I/O, no throwing.

```php
final readonly class ReferenceAvailableConstraint
{
    public const string REFERENCE_ALREADY_USED = 'reference_already_used';

    /**
     * @param SubscriptionDataModel[]              $existingSubscriptions
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(string $reference, array $existingSubscriptions, array $violations = []): array
    {
        foreach ($existingSubscriptions as $existing) {
            if ($reference === $existing->reference) {
                $violations['reference'][] = self::REFERENCE_ALREADY_USED;
            }
        }

        return $violations;
    }
}
```

Rules:

- **Accumulate, never fail fast.** A caller must be able to fix every field in one round trip.
  There is a dedicated unit test for this (§9.1).
- Error codes are `public const string` on the class that emits them — `ERROR_CODE` for a
  validator with one outcome, named constants (`ILLEGAL_STATUS_CODE`, `NOT_ACTIVABLE_CODE`) when
  several.
- Constraints receive the data models they compare against; **they never query**. The use case loads
  what the constraint needs. That is what keeps constraints static and instantly unit-testable.
- Validators throw; constraints return.

### 6.9 Factories

Two families, both `final readonly`, both with the same two-method shape:

- `Domain/Factory/DataModelFactory/<Noun>DataModelFactory` — builds a data model (or a projection
  data model) from other data models/DTOs. `buildOne(): ?XDataModel` returns `null` when the source
  is not eligible;
  `buildMany(array): array` filters the nulls out.
- `Domain/Factory/OutputFactory/<Thing>OutputFactory` — builds a DataOutput from a data model, using
  the object mapper for the declarative part and hand-written code for the rest.
  **Every DataOutput has one, always, even when it would be trivial.**

```php
final readonly class SubscriptionOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param SubscriptionDataModel[] $subscriptions
     *
     * @return SubscriptionDataOutput[]
     */
    public function buildMany(array $subscriptions): array
    {
        $outputs = [];
        foreach ($subscriptions as $subscription) {
            $outputs[] = $this->buildOne($subscription);
        }

        return $outputs;
    }

    public function buildOne(SubscriptionDataModel $subscription): SubscriptionDataOutput
    {
        $output = $this->mapper->map($subscription, SubscriptionDataOutput::class);

        if (null !== $subscription->trial) {
            $output->trialEndsAt = DateDataTransformer::dateToString($subscription->trial->endsAt);
        }

        return $output;
    }
}
```

**The OutputFactory is mandatory, not conditional.** A use case never injects
`ObjectMapperInterface` and never calls `map()` itself: it asks the factory for
`buildOne()` / `buildMany()` and that is the only way a DataOutput comes into existence. The cost
is a handful of near-empty classes; the benefits are worth it:

- one single place to look when an endpoint's payload is wrong;
- the day a field needs logic, the signature at the call site does not change;
- the `buildMany()` loop is written once per output instead of inlined in every `List…UseCase`;
- output construction becomes unit-testable without a container.

A factory that is still a bare `map()` call after a year is not a smell — it is the seam that let
you add `trialEndsAt` without touching five use cases.

### 6.10 UseCase

The application service. One per user intent.

```php
<?php

declare(strict_types=1);

namespace App\UseCase\Subscription;

use App\Domain\DTO\Input\Subscription\CreateSubscriptionDataInput;
use App\Domain\DTO\Output\Subscription\SubscriptionDataOutput;
use App\Domain\DTO\DataModel\AccountDataModel;
use App\Domain\DTO\DataModel\SubscriptionDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\SubscriptionOutputFactory;
use App\Domain\Gateway\Persister\SubscriptionPersisterGateway;
use App\Domain\Gateway\Provider\AccountProviderGateway;
use App\Domain\Validation\Validator\Subscription\CreateSubscriptionValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class CreateSubscriptionUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateSubscriptionValidator $validator,
        private AccountProviderGateway $accountProviderGateway,
        private SubscriptionPersisterGateway $subscriptionPersisterGateway,
        private SubscriptionOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     * @throws DataModelNotFoundException
     */
    public function execute(CreateSubscriptionDataInput $input): SubscriptionDataOutput
    {
        $account = $this->accountProviderGateway->findOneByReference($input->accountReference);
        if (null === $account) {
            throw new DataModelNotFoundException(AccountDataModel::class);
        }

        $this->validator->validate($input, $account->subscriptions->toArray());

        $subscription = new SubscriptionDataModel();
        $subscription->account = $account;
        $subscription->reference = $input->reference;
        $subscription->discountRate = $input->discountRate ?? $account->defaultDiscountRate;
        $subscription->comments = $input->comments;

        $this->subscriptionPersisterGateway->create($subscription);

        return $this->outputFactory->buildOne($subscription);
    }
}
```

Rules:

- `final readonly`, implements the empty marker `UseCaseInterface`, **exactly one public method
  named `execute`**. Private helpers are allowed; a second public method means you need a second
  use case.
- Signature is **typed-direct**: `execute(XDataInput $input)`, or
  `execute(int $id, XDataInput $input)` when the identity comes from the route, or
  `execute(int $id)` / `execute(string $reference)` for lookups and state transitions that take no
  body. No arrays, no `mixed`, no `Request`.
- Return type is whatever the use case naturally produces: a DataOutput, `DataOutput[]` (annotate
  with `@return XDataOutput[]`), a scalar, or `void`.
- Canonical body order: **load → validate → mutate → persist → build the output**. Validate as
  early as the data it needs allows.
- **After persisting a child, add it to its parent's collection.** Doctrine writes the foreign
  key from the owning side, but the parent already in the identity map keeps the collection it
  was hydrated with — so anything reading the parent later in the same request sees stale
  state, a derived status included.
- A use case **never injects `ObjectMapperInterface`**. Outputs come from an OutputFactory (§6.9).
- Missing data model → `DataModelNotFoundException`, constructed with the **data model class name**
  (`new DataModelNotFoundException(AccountDataModel::class)`).
- Every thrown exception is declared in `@throws`.
- A use case may orchestrate other use cases only through the container when the intent genuinely
  nests (an `Import…` driving a `Create…`); prefer extracting a shared domain service first.

### 6.11 Controller

Thin. Build the DataInput (via the argument resolver), call the use case, wrap in `JsonResponse`.
No business logic, no data model access, no try/catch — the exception listener owns error responses.

```php
#[OA\Tag(name: 'Subscriptions')]
final class SubscriptionController extends AbstractController
{
    #[Route('/subscriptions', methods: Request::METHOD_GET)]
    #[OA\Parameter(name: 'accountReference', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'List the subscriptions of an account.',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: SubscriptionDataOutput::class))),
    )]
    public function listSubscriptions(#[MapDataInput] ListSubscriptionsDataInput $input, ListSubscriptionsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($input));
    }

    #[Route('/subscriptions/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_PUT)]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new Model(type: UpdateSubscriptionDataInput::class))]
    #[OA\Response(response: Response::HTTP_OK, content: new OA\JsonContent(ref: new Model(type: SubscriptionDataOutput::class)))]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Subscription not found.')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY)]
    public function updateSubscription(int $id, #[MapDataInput] UpdateSubscriptionDataInput $input, UpdateSubscriptionUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute($id, $input));
    }

    #[Route('/subscriptions/{id}', requirements: ['id' => '\d+'], methods: Request::METHOD_DELETE)]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Subscription deleted.')]
    public function deleteSubscription(int $id, DeleteSubscriptionUseCase $useCase): JsonResponse
    {
        $useCase->execute($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
```

Rules:

- One controller per aggregate **per audience**, `final`, extends `AbstractController`. Each
  audience gets its own folder under `Controller/` and no controller sits loose at the root:
  `Controller/User/` for what the website calls, `Controller/Admin/` for the back-office. An
  audience is a set of routes sharing an authorization rule and a client, so a new one (a partner
  API, a webhook surface) is a new folder, not a suffix.
- **The `Admin` prefix on the class name stays**, because `Controller\Admin\UserController` and
  `Controller\User\UserController` would be two classes with the same short name, imported side
  by side in a review. The public side keeps the bare name.
- Authorization is declared once in `security.yaml`'s `access_control` (`^/api/admin` →
  `ROLE_ADMIN`), not as an attribute repeated on every route.
- Watch out for method names `AbstractController` already defines: `getUser()` is taken, so the
  route method is `getUserAccount()`.
- **Use cases are injected as method arguments**, not constructor arguments — a controller holding
  eight use cases in its constructor instantiates all of them on every request.
- Routes are declared with `#[Route]` attributes, loaded from `Infrastructure/Controller/` with
  `format: json`. Numeric path params always carry `requirements: ['id' => '\d+']`.
- Every route carries full OpenAPI attributes: `#[OA\Tag]` on the class, `#[OA\Parameter]`,
  `#[OA\RequestBody]` and one `#[OA\Response]` per status code the endpoint can return. This is
  the published API documentation — treat it as part of the contract, not decoration.
- Status codes: `200` read/update, `201` create, `204` delete, `404` not found, `422` validation.

### 6.12 The `#[MapDataInput]` argument resolver

Rather than Symfony's native `#[MapQueryString]` / `#[MapRequestPayload]`, the blueprint ships its
own resolver, which merges **query string and JSON body** into one DataInput and produces a
uniform, loggable failure. It is ~120 lines to maintain, and it buys three things the native
attributes do not:

- **One attribute, whatever the transport.** A controller signature does not change when a filter
  moves from the query string to the body, and a single DataInput can carry both.
- **A diagnosable failure.** A mapping error logs the route, method, content type and length,
  payload keys, the serializer's reason, the missing constructor arguments and the invalid path —
  and the raw payload. That is what turns "a caller broke the contract" from a ticket into a log
  line.
- **An explicit opt-out for secrets**, via `SensitiveDataInputInterface`. Being logged is the
  default; not being logged is a declaration on the DTO, visible in review.

```php
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class MapDataInput extends ValueResolver
{
    public function __construct(string $resolver = DataInputValueResolver::class)
    {
        parent::__construct($resolver);
    }
}
```

`DataInputValueResolver`:

1. Rejects (with `\LogicException`) any target not typed as a `DataInputInterface` — a
   programming error, not a runtime one.
2. Builds the payload as `array_replace($request->query->all(), $bodyData)`, so one DataInput
   serves both `GET ?a=1` and `POST {json}`.
3. Denormalises with `['filter_bool' => true]` **and
   `AbstractObjectNormalizer::ENABLE_TYPE_CONVERSION => true`**, because a query string carries
   strings and nothing else: `"2"` has to become the `int` the DataInput declares and `"true"` the
   `bool`. Without that flag a paginated `?perPage=2` fails to map — this is what makes one
   DataInput genuinely serve both `GET ?a=1` and `POST {json}`.
4. On failure, logs route, method, content-type, content-length, payload keys, the serializer
   reason, missing fields and invalid path — **and the raw payload, unless the DataInput
   implements `SensitiveDataInputInterface`**. Opting out is explicit; being logged is the
   default.
5. Throws `DataInputMappingException` with a generic client-facing message. Internals never leak
   to the caller.

### 6.13 Exceptions and the error contract

| Exception | Namespace | HTTP | Body |
| --- | --- | --- | --- |
| `ValidationException` | `Domain\Exception` | 422 | `{"violations": {"field": ["error_code"]}}` |
| `DataInputMappingException` | `Infrastructure\Exception` | 422 | `{"violations": "The data provided seems to be invalid."}` |
| `DataModelNotFoundException` | `Infrastructure\Exception` | 404 | `{"message": "DataModel App\\Domain\\DTO\\DataModel\\XDataModel not found"}` |
| `InvalidCredentialsException` | `Domain\Exception` | 401 | `{"message": "invalid_credentials"}` |
| `AccountDeactivatedException` | `Domain\Exception` | 403 | `{"message": "account_deactivated"}` |

The two authentication answers are **domain** exceptions: refusing a sign-in is a business rule,
not a transport failure. One code covers an unknown identifier, a wrong password and an unusable
token — telling them apart would tell a stranger which accounts exist. A deactivated account gets
its own code, and only **after** the credentials checked out, so the distinction leaks nothing.

```php
final class ValidationException extends \Exception
{
    /** @param array<string, list<string>> $violations */
    public function __construct(
        public readonly string $errorCode,
        public readonly array $violations = [],
    ) {
        parent::__construct(sprintf('Validation failed: %s', $errorCode));
    }
}
```

A single `#[AsEventListener] final readonly class ExceptionListener` maps them to responses. It is
the only place in the app that turns an exception into HTTP. Adding a new error shape means adding
a branch there, not a try/catch in a controller.

Note the asymmetry to respect: `ValidationException` is a **domain** concept (business rules
rejected the input); `DataModelNotFoundException` and `DataInputMappingException` are
**infrastructure** concepts (the transport/persistence layer could not find or parse something).
Use cases import the infrastructure ones — that is the one deliberate, documented exception to
"UseCase does not import Infrastructure".

`DataModelNotFoundException` is **always** constructed with the data model class
(`new DataModelNotFoundException(AccountDataModel::class)`), never with a free-text message: the class is what
makes the 404 bodies uniform and greppable.

### 6.14 Console commands

```php
#[AsCommand(
    name: 'billing:subscription:expire',
    description: 'Expire subscriptions whose term has ended',
)]
final class ExpireSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly AccountProviderGateway $accountProviderGateway,
        private readonly ExpireSubscriptionsUseCase $useCase,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('account-reference', InputArgument::REQUIRED, 'Account reference');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Report without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $account = $this->accountProviderGateway->findOneByReference($input->getArgument('account-reference'));
        if (null === $account) {
            $output->writeln('<error>Unknown account</error>');

            return Command::FAILURE;
        }

        $report = $this->useCase->execute($account->id);
        $output->writeln("<info>{$report->count} subscriptions expired</info>");

        return Command::SUCCESS;
    }
}
```

Rules: `final`, `#[AsCommand]` (never a static `$defaultName`), arguments in kebab-case, always
return `Command::SUCCESS` / `Command::FAILURE`, all business logic in the use case. A command may
loop over data models fetched from a gateway and call the use case once per item — that iteration is
the command's job. Developer-only commands go under `Command/Dev/`.

### 6.15 Outbound integrations (pluggable third parties)

When the app talks to several interchangeable third parties (payment providers, carriers, catalog
suppliers), use **tagged services + a service locator keyed by the vendor's own name**. No
`services.yaml` map to maintain, no `match` statement to grow.

```php
#[AutoconfigureTag('app.payment_http_client')]
interface PaymentHttpClientInterface
{
    /** @return ChargeDTO[] */
    public function listCharges(AccountDataModel $account, array $parameters = []): array;

    public function getBalance(AccountDataModel $account): float;

    /** The key this client is registered under — its own vendor prefix. */
    public static function getVendorPrefix(): string;
}
```

```php
final readonly class PaymentHttpClientResolver
{
    /** @param ServiceLocator<PaymentHttpClientInterface> $clients */
    public function __construct(
        #[TaggedLocator(tag: 'app.payment_http_client', defaultIndexMethod: 'getVendorPrefix')]
        private ServiceLocator $clients,
    ) {
    }

    public function get(string $name): PaymentHttpClientInterface
    {
        $name = trim(strtolower($name));
        if (false === $this->clients->has($name)) {
            throw new \InvalidArgumentException(sprintf('Unknown payment HTTP client "%s".', $name));
        }

        return $this->clients->get($name);
    }
}
```

An abstract base owns the cross-cutting concerns — authentication hook, request execution, and
**persisting one API-call log row per request, success or failure**:

```php
abstract readonly class AbstractPaymentHttpClient
{
    public function __construct(
        protected ApiLogPersisterGateway $logPersister,
        protected HttpClientInterface $httpClient,
    ) {
    }

    abstract protected function getAuthentication(AccountDataModel $account, array $parameters = []): string;

    abstract protected function sendRequest(AccountDataModel $account, string $method, string $endpoint, array $parameters = []): array;

    protected function requestWithLog(AccountDataModel $account, string $method, string $endpoint, array $parameters = []): ResponseInterface
    {
        $log = new ApiLogDataModel();
        $log->endpoint = $endpoint;

        try {
            $response = $this->httpClient->request($method, $endpoint, $parameters);
            $log->statusCode = $response->getStatusCode();
            $log->response = $response->getContent();
            $this->logPersister->create($log);

            return $response;
        } catch (\Exception $exception) {
            $log->statusCode = $exception->getCode();
            $log->response = $exception->getMessage();
            $this->logPersister->create($log);

            throw $exception;
        }
    }

    protected function validateContract(AccountDataModel $account, string $contractName, array $payload): void
    {
        // JSON-Schema check of the third-party response against schemas/…
    }
}
```

Each vendor then contributes three classes and nothing else:

1. `<Vendor>HttpClient` — endpoints, auth, pagination.
2. `<Vendor>DTOFactory` implementing `<Integration>DTOFactoryInterface` — translates the vendor's
   payload into the app's own `…DTO`, **returning `null` and logging on a malformed record**
   rather than throwing, so one bad row does not abort an import of ten thousand.
3. A row of tests with a recorded payload fixture under `fixtures/<Integration>Mock/`.

Adding a vendor requires **zero configuration changes**.

Third-party payload shapes live in `Infrastructure/DTO/<Integration>/` and never escape
Infrastructure — a use case receives data models or app DTOs, never a vendor's shape.

### 6.16 Inbound events (pluggable handlers)

The same tagged-service pattern, applied to an inbound event bus. One endpoint receives every
event; a finder routes it; a JSON Schema validates the body before the handler runs.

```php
#[AutoconfigureTag('app.domain_event_handler')]
interface DomainEventHandlerInterface
{
    /** The event name this handler owns — the finder's index key. */
    public static function getSupportedEventName(): string;

    /** JSON Schema file (relative to the schema folder) describing the event body. */
    public static function getSchemaFile(): string;

    public function handle(EventEnvelopeInput $event): void;
}
```

```php
final readonly class DomainEventHandlerFinder
{
    /** @var array<string, DomainEventHandlerInterface> */
    private array $handlersByEventName;

    /** @param iterable<DomainEventHandlerInterface> $handlers */
    public function __construct(
        #[AutowireIterator('app.domain_event_handler')] iterable $handlers,
    ) {
        $handlersByEventName = [];
        foreach ($handlers as $handler) {
            $eventName = $handler::getSupportedEventName();
            if (true === isset($handlersByEventName[$eventName])) {
                throw new \LogicException(sprintf('Two handlers declare the same event "%s".', $eventName));
            }
            $handlersByEventName[$eventName] = $handler;
        }

        $this->handlersByEventName = $handlersByEventName;
    }

    public function findByEventName(string $eventName): ?DomainEventHandlerInterface
    {
        return $this->handlersByEventName[$eventName] ?? null;
    }
}
```

The consuming use case:

```php
public function execute(EventEnvelopeInput $event): SimpleReportDataOutput
{
    $this->envelopeValidator->validate($event);

    $handler = $this->handlerFinder->findByEventName($event->name);
    if (null === $handler) {
        $this->logger->info("Event {$event->name} is not consumed by this service.", [
            'event_id' => $event->id, 'source' => $event->source, 'event_name' => $event->name,
        ]);

        return new SimpleReportDataOutput(sprintf('Event "%s" ignored', $event->name));
    }

    $this->schemaValidator->validate($handler::getSchemaFile(), $event->body);

    $handler->handle($event);

    return new SimpleReportDataOutput("Event {$event->name} is processed.");
}
```

Key decisions to carry over:

- **An unknown event is acknowledged, not rejected.** Buses usually filter on source, not name, so
  the service receives events it does not care about. Returning 2xx with an "ignored" report keeps
  them from being redelivered forever.
- **A handler is the single source of truth for its event**: the name it answers to and the schema
  its body must match are both static methods on the handler class.
- Duplicate registrations fail at container compile time, not at runtime.
- Event names are versioned strings held in a `Registry`:
  `'event.account.account-created.v1'`.
- Handlers that share a topic extract an `Abstract<Topic>EventHandler` for the common payload
  extraction.
- Schemas live in `schemas/import/<event-name>.json`, one file per event **version**.

### 6.17 Domain services

Business logic that is neither a use case (no orchestration, no persistence) nor a validator lives
in `Domain/<SubDomain>/<Name>.php` as a `final readonly` class with a verb-ish or
predicate-ish public method. **This is the one family with no mandatory suffix** — the class name
states the concept (`SubscriptionEligibility`, `RenewalSchedule`), and a `…Service` suffix would
add nothing. In exchange, the folder is not free: `<SubDomain>` must be the aggregate the service
serves, so `Domain/Subscription/SubscriptionEligibility.php`, never a folder named after some
unrelated neighbour.

```php
final readonly class SubscriptionEligibility
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function isRenewable(SubscriptionDataModel $subscription): bool { /* … */ }
}
```

These are the one place in `Domain/` that may take PSR interfaces (`LoggerInterface`,
`CacheItemPoolInterface`, `ClockInterface`) — abstractions, not framework. They are tested like
use cases if they touch I/O, like validators if they are pure.

### 6.18 DataTransformers

Two families with the **same suffix but different roles and different layers**. The distinction is
not cosmetic: it is what keeps `Domain/` free of `Infrastructure/` imports.

| | `Domain/DataTransformer/` | `Infrastructure/DataTransformer/` |
| --- | --- | --- |
| Role | format a value **we own** for output | parse / sanitise a value **someone else sent us** |
| Purity | pure static functions, scalars in, scalars out, no dependency | may use libraries, may log, may know a vendor's quirks |
| Typical | `DateDataTransformer::dateToString()`, `MoneyDataTransformer::toMinorUnits()` | `HtmlDataTransformer::sanitize()`, `VendorPayloadDataTransformer::normalise()` |
| Referenced from | `#[Map(transform: …)]` on DataOutputs, OutputFactories, domain services | vendor DTO factories, HTTP clients, denormalisers |
| May be imported by Domain | **yes** | **never** |

```php
// src/Domain/DataTransformer/DateDataTransformer.php
final readonly class DateDataTransformer
{
    /** The one date format the API speaks: ISO 8601 with offset. */
    public const string FORMAT = 'Y-m-d\TH:i:sP';

    public static function dateToString(?\DateTimeInterface $date): ?string
    {
        return $date?->format(self::FORMAT);
    }
}
```

Rules:

- Methods are `public static`, pure, and named `<from>To<To>` (`dateToString`, `floatToString`).
- **One transformer per value type, one behaviour per value type.** Two classes formatting dates
  differently is the bug this split exists to prevent — the format lives in a single `const`.
- If you catch yourself wanting an `Infrastructure` transformer inside a DataOutput, the function
  is pure and belongs in `Domain/`. Move it.

### 6.19 Outbound contracts that are not gateways

A Gateway covers **data access**. Everything else the Domain needs from the outside world —
hashing a password, signing a token, sending a mail — gets the same treatment under a different
name: **an interface in `Domain/<SubDomain>/<Name>Interface.php`, implemented in
`Infrastructure/<Concern>/`**.

```php
// src/Domain/User/PasswordHasherInterface.php — what the Domain needs
interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function verify(string $hash, string $plainPassword): bool;
}

// src/Infrastructure/Security/PasswordHasher.php — how it is done
final readonly class PasswordHasher implements PasswordHasherInterface { /* … */ }
```

Rules:

- The interface lives in the sub-domain that needs it, not in a `Contract/` bucket: the concept
  belongs to `User`, or to `Session`, not to a folder full of unrelated interfaces.
- `…Interface` suffix, because the bare concept name is taken by the implementation.
- Exactly one implementation, so autowiring binds it without a `services.yaml` alias — same rule
  as a gateway. Two implementations mean the contract is doing two jobs.
- A use case depends on the interface only. It must be substitutable in a unit test with a mock
  and nothing else.
- **No framework interface ever reaches a data model to make this work.** When the vendor's API
  demands an object of its own (`PasswordAuthenticatedUserInterface`, `UserInterface`), the
  adapter is an Infrastructure class built from the data model (`SecurityUser::fromDataModel()`),
  never the data model itself.

---

## 7. Code style

Enforced by PHP-CS-Fixer (`@PSR12` + `@Symfony` + `declare_strict_types`, risky rules allowed) and
PHPStan level 8. Rules the tools cannot enforce, enforced by review:

1. **`declare(strict_types=1);`** in every file, on the line after `<?php`.
2. **`final` by default.** Exceptions: data models (Doctrine proxies), abstract classes, interfaces,
   traits. `readonly` whenever the class holds only injected dependencies — so every use case,
   validator, factory, persister-free service is `final readonly`.
3. **Yoda comparisons, always strict, always explicit.** The constant goes on the left:
   ```php
   if (true === $isActive) { }      // good
   if (null === $account) { }       // good
   if (false === empty($violations)) { }  // good
   if ($isActive) { }               // rejected
   if ($isActive === true) { }      // rejected
   if (!$isActive) { }              // rejected — use false === $isActive
   ```
   This applies to **every** type, not just booleans.
4. **No getters/setters on data models and DTOs.** Public properties.
5. **Constructor property promotion** for dependencies and for readonly DTOs; plain declared
   properties for mutable output DTOs.
6. **Full PHPDoc generics.** PHPStan level 8 means every `array` gets a shape:
   `@return list<SubscriptionDataModel>`, `@param array<string, mixed> $parameters`,
   `@var Collection<int, InvoiceDataModel>`. Annotate `@throws` on anything that throws.
7. **Typed constants**: `public const string ERROR_CODE = '…';`
8. Comments explain **why**, in English, and are welcome on non-obvious columns, deliberate
   deviations and workarounds. Do not narrate what the code says.
9. Mark superseded code `@deprecated <what to use instead>` and keep a migration note. Do not
   silently leave two ways to do the same thing (see §14).

---

## 8. Dependency injection conventions

`../config/services.yaml` stays almost empty. The default block autowires and autoconfigures
everything under `../../../src`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    Psr\Clock\ClockInterface: '@Symfony\Component\Clock\NativeClock'
    Symfony\Component\Clock\NativeClock: ~

    App\:
        resource: '../src/'
        exclude:
            - '../src/Kernel.php'
```

Only three things justify an explicit entry:

1. **Scalar/parameter arguments** — `$bucket: '%env(AWS_S3_BUCKET)%'`,
   `$schemaFolder: '%kernel.project_dir%/%events_schema_folder%'`.
2. **Third-party classes** that cannot be autowired (SDK clients, factories).
3. **Interface → implementation aliases** where more than one implementation exists. If you find
   yourself writing one for a Gateway, the gateway is wrong: split it.

Parameters live in `../config/config.yaml`, imported by `services.yaml`, and always read from env
vars. Never hardcode an environment-dependent value in `services.yaml`.

Interchangeable implementations use `#[AutoconfigureTag]` on the interface plus `#[TaggedLocator]`
or `#[AutowireIterator]` at the consumer — never a hand-maintained YAML map.

---

## 9. Tests

Two suites in `../phpunit.xml`, both run inside the dedicated test containers against a **real
MySQL**. `dama/doctrine-test-bundle` is registered as a PHPUnit extension and wraps every test
method in a transaction that is rolled back on teardown, so tests are isolated without truncating.

```
tests/
├── bootstrap.php
├── Unit/           # mirrors src/ exactly
└── Integration/
    ├── LoadFixturesTrait.php
    └── …           # mirrors src/; seed data comes from src/Fixtures/
```

**Test paths mirror source paths.** `src/UseCase/Subscription/CreateSubscriptionUseCase.php` →
`tests/Integration/UseCase/Subscription/CreateSubscriptionUseCaseTest.php`.

### 9.1 Mandatory coverage

| Source class | Test kind | Location |
| --- | --- | --- |
| every concrete `App\UseCase\…UseCase` | **integration** | mirrored path |
| every concrete `App\Domain\Validation\Validator\…Validator` | **unit** | mirrored path |
| every `…Constraint` | unit | mirrored path |
| every `…Factory` | unit | mirrored path |
| every `…HttpClient` | integration, with a recorded payload fixture | mirrored path |
| every `…EventHandler` | integration | mirrored path |

These are hard requirements, checked in review. Everything else is tested when it carries risk.

### 9.2 Unit tests (validators)

Extends `PHPUnit\Framework\TestCase`. Gateways are `$this->createMock(...)`. The real
`symfony/validator` is built with `Validation::createValidatorBuilder()->enableAttributeMapping()`
so the attribute constraints are genuinely exercised.

Required methods:

1. **happy path** — no exception;
2. **one method per violation rule** — each error code has its own test;
3. **one accumulation test** — several rules broken at once produce **one**
   `ValidationException` containing **all** the violations. This is the test that stops someone
   "optimising" the validator into failing fast.

```php
public function testItAccumulatesEveryViolation(): void
{
    try {
        $this->validator->validate(new CreateSubscriptionDataInput(
            accountReference: '',
            planCode: '',
            discountRate: 9999.0,
        ), []);
        self::fail('Expected ValidationException');
    } catch (ValidationException $exception) {
        self::assertSame(CreateSubscriptionValidator::ERROR_CODE, $exception->errorCode);
        self::assertArrayHasKey('accountReference', $exception->violations);
        self::assertArrayHasKey('planCode', $exception->violations);
        self::assertArrayHasKey('discountRate', $exception->violations);
    }
}
```

Other rules:

- Add `#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]` on the class when
  mocks only stub. Drop it and use `expects(self::once())` when call counts matter.
- **Do not** unit-test the runtime input-type guard — the typed signature is PHP's job.
- Readonly DataInputs needing adversarial values that the constructor forbids are built with
  `(new \ReflectionClass(XDataInput::class))->newInstanceWithoutConstructor()` and every property
  set by reflection. `setValue` on an already-constructed readonly property is rejected by PHP 8.4.

### 9.3 Integration tests (use cases)

Extends `KernelTestCase`, pulls the use case **and the gateways** from `self::getContainer()`
(the test container exposes private services).

```php
final class CreateSubscriptionUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private CreateSubscriptionUseCase $useCase;
    private SubscriptionProviderGateway $subscriptionProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = $this->getContainer()->get(CreateSubscriptionUseCase::class);
        $this->subscriptionProviderGateway = $this->getContainer()->get(SubscriptionProviderGateway::class);

        $this->loadFixtures(AccountFixtures::class, PlanFixtures::class);
    }

    public function testItCreatesSubscription(): void
    {
        $output = $this->useCase->execute(new CreateSubscriptionDataInput(
            accountReference: 'acc-1',
            planCode: 'standard',
        ));

        self::assertSame('standard', $output->planCode);
        self::assertFalse($output->isActive);

        // Re-read through the gateway: assert it was really persisted.
        $subscription = $this->subscriptionProviderGateway->findOneById($output->id);
        self::assertNotNull($subscription);
        self::assertSame('standard', $subscription->planCode);
    }

    public function testItRejectsDuplicateReference(): void
    {
        $this->useCase->execute(new CreateSubscriptionDataInput(accountReference: 'acc-1', planCode: 'standard'));

        try {
            $this->useCase->execute(new CreateSubscriptionDataInput(accountReference: 'acc-1', planCode: 'standard'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSubscriptionValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('reference', $exception->violations);
        }
    }
}
```

Rules:

- **Happy path asserts the returned DataOutput *and* re-reads through the relevant
  `…ProviderGateway`** to prove persistence and derived state.
- **One test method per validation / not-found / unauthorized branch.** Use `try/catch` and
  inspect `$exception->violations` and `$exception->errorCode`; bare `expectException()` is too
  coarse — it passes when the wrong rule fires.
- **Conflict scenarios are set up by an in-test `execute(...)` call, never by fixtures.** Every
  test is self-contained and readable on its own.
- Test method names are sentences: `testItRejectsDuplicateReference`,
  `testItDeactivatesSiblingsOnActivate`.

### 9.4 Fixtures in tests

A test names the fixture classes it needs; `LoadFixturesTrait` runs exactly those, in dependency
order, against the test database.

```php
trait LoadFixturesTrait
{
    /**
     * @param class-string<FixtureInterface> ...$fixtureClasses
     */
    private function loadFixtures(string ...$fixtureClasses): void
    {
        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $loader = new Loader();
        foreach ($fixtureClasses as $fixtureClass) {
            $loader->addFixture($container->get($fixtureClass));
        }

        // DELETE, never TRUNCATE: MySQL implicitly commits around TRUNCATE, which would break
        // out of the transaction dama/doctrine-test-bundle wraps this test method in.
        $purger = new ORMPurger($entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);

        (new ORMExecutor($entityManager, $purger))->execute($loader->getFixtures());

        $entityManager->clear();
    }
}
```

```php
protected function setUp(): void
{
    parent::setUp();

    $this->useCase = $this->getContainer()->get(CreateSubscriptionUseCase::class);
    $this->subscriptionProviderGateway = $this->getContainer()->get(SubscriptionProviderGateway::class);

    $this->loadFixtures(AccountFixtures::class, PlanFixtures::class);
}
```

Four consequences to design around:

1. **Fixtures are reloaded for every test method** — the rollback undid them. Keep each fixture
   class small and focused so a test can pick only what it needs.
2. **The purge runs in DELETE mode**, for the `TRUNCATE` reason above. DELETE leaves
   `AUTO_INCREMENT` counters untouched, so ids differ from one test method to the next.
3. **Therefore a test never hardcodes an id.** It reaches its seed rows through the fixture's
   references (`$this->getReference(AccountFixtures::ACME, AccountDataModel::class)`) or through a
   gateway lookup on a business key. This is the main practical gain over a YAML fixture DSL,
   where fixed ids were the only way to find anything again.
4. **Fixtures are resolved from the container**, by `ContainerFixtureLoader` (a `Loader` whose
   `createFixture()` asks the container). Doctrine's own loader instantiates with `new $class()`,
   which cannot work once a fixture writes through a persister gateway. The side benefit: a
   `DependentFixtureInterface` dependency is pulled in and wired for free, so a test names the
   fixture it cares about and its dependencies come along.

If a test genuinely needs a fixed id — a URL asserted verbatim, a legacy contract — set it with an
assigned generator on that data model's metadata inside the fixture, and say in a comment why.

---

## 10. Fixtures

Seed data is **plain PHP**, through `doctrine/doctrine-fixtures-bundle` — no YAML DSL, no
external fixture library. One class per aggregate in `src/Fixtures/<Noun>Fixtures.php`:

```php
<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\AccountDataModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AccountFixtures extends Fixture
{
    public const string ACME = 'account-acme';

    public function load(ObjectManager $manager): void
    {
        $account = new AccountDataModel();
        $account->reference = 'acme';
        $account->name = 'Acme Inc.';
        $account->isActive = true;

        $manager->persist($account);
        $manager->flush();

        $this->addReference(self::ACME, $account);
    }
}
```

```php
final class SubscriptionFixtures extends Fixture implements DependentFixtureInterface
{
    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [AccountFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $subscription = new SubscriptionDataModel();
        $subscription->account = $this->getReference(AccountFixtures::ACME, AccountDataModel::class);
        // …
    }
}
```

Why PHP rather than a YAML fixture library:

- fixtures are typed, so a renamed property breaks at static-analysis time instead of at load
  time — PHPStan level 8 covers them like any other code;
- they are refactored by the IDE along with the data models;
- ordering is explicit through `DependentFixtureInterface`, not implicit through filenames;
- cross-fixture wiring goes through named references (`addReference` / `getReference`) instead of
  string ids, which is what lets tests stop depending on `AUTO_INCREMENT` values;
- one less bundle, one less DSL to learn, and no gap between what the ORM accepts and what the
  DSL can express.

Rules:

- `final`, one class per aggregate, named `<Noun>Fixtures`.
- Reference keys are `public const string` on the fixture that creates the row — that constant is
  the contract other fixtures and tests use.
- Group them with `FixtureGroupInterface` when the dev environment needs a subset
  (`doctrine:fixtures:load --group=demo`).
- They are autoconfigured (`doctrine.fixture.orm`); no `services.yaml` entry.
- Loading is `make load-fixtures`, never `doctrine:fixtures:load` typed by hand — and never
  against anything but the dev or test database.

Recorded third-party payloads, sample inbound events and CSV feeds are **not** Doctrine fixtures:
they stay as files under `fixtures/` at the repository root, shared between the dev environment
and the integration tests — one recording, two consumers.

---

## 11. Configuration

```
config/
├── bundles.php                  # explicit bundle list with per-env flags
├── config.yaml                  # app parameters, all sourced from env vars
├── services.yaml                # imports config.yaml; see §8
├── routes.yaml                  # controller resource, format: json
├── routes/{dev,prod}/…          # env-specific routes (API docs exposure)
└── packages/
    ├── <bundle>.yaml            # shared config
    └── {dev,test,prod}/<bundle>.yaml   # env overrides
```

**One mapping override is mandatory.** Doctrine defaults to `src/Entity` with the `App\Entity`
prefix; point it at the data models instead, or nothing is mapped:

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        mappings:
            App:
                type: attribute
                dir: '%kernel.project_dir%/src/Domain/DTO/DataModel'
                prefix: 'App\Domain\DTO\DataModel'
                alias: App
```

**Environment variables.** Document the loading order in `../docs/env_vars.md` and keep it true:

1. `../.env` — the **canonical, complete list**, with a local-dev default for every variable. This
   file is the inventory; a variable that is not in it does not exist.
2. `../docker-compose.yml` — per-container overrides (notably the `app-test` / `mysql-test` pair,
   which point at a dedicated database so tests never touch the working one).
3. Deployment HCL/Terraform per environment, then a secrets file — for stage/prod.

Each step overrides the previous one. New variable → add it to `../.env` first, always.

---

## 12. Database and migrations

- Schema changes ship as Doctrine migrations in `migrations/Version<UTCtimestamp>.php`. Never
  `doctrine:schema:update` outside a scratch database.
- Table names are snake_case singular (`subscription`, `account_plan`).
- **Regenerate the schema documentation on every migration** and commit it alongside:
  `docs/dev/database-schema.html` (an HTML dump of the full schema) and, where the project
  keeps one, a generated diagram (`docs/dev/<name>-schema.svg`, produced by a script in the
  same folder and derived from the Doctrine mapping). Only regenerate when a table in scope
  actually changed.
- The dev database and the test database are separate containers with separate credentials.

---

## 13. Adding a feature — the checklist

Adding a "create X" endpoint, end to end:

1. **DataModel** `Domain/DTO/DataModel/XDataModel.php` — public props, `DataModelInterface`,
   `createdAt`/`updatedAt`.
2. **Migration** — `make` a migration, apply it, regenerate the schema spec.
3. **Gateways** — `Domain/Gateway/Provider/XProviderGateway.php` (the query the use case needs,
   context-named) and/or `Domain/Gateway/Persister/XPersisterGateway.php`.
4. **Implementations** — `Infrastructure/Repository/XRepository.php` (joins + `addSelect` for
   every association read downstream) and `Infrastructure/Persister/XPersister.php`.
5. **DataInput** `Domain/DTO/Input/X/CreateXDataInput.php` — `final readonly`, `#[Assert\…]`,
   `SensitiveDataInputInterface` if it carries secrets.
6. **DataOutput** `Domain/DTO/Output/X/XDataOutput.php` — `#[Map]` attributes, dates through
   `Domain\DataTransformer\DateDataTransformer` — **plus its mandatory**
   `Domain/Factory/OutputFactory/XOutputFactory.php` (`buildOne` / `buildMany`).
7. **Constraints** for each cross-model rule, static and pure.
8. **Validator** `Domain/Validation/Validator/X/CreateXValidator.php` — accumulates, throws one
   `ValidationException` with an `ERROR_CODE`.
9. **UseCase** `UseCase/X/CreateXUseCase.php` — `final readonly`, single `execute`,
   load → validate → mutate → persist → `outputFactory->buildOne()`.
10. **Controller route** in `Infrastructure/Controller/XController.php` — `#[MapDataInput]`, use
    case injected as a method argument, full OpenAPI attributes.
11. **Unit test** for the validator (happy path + one per rule + accumulation) and for each
    constraint/factory.
12. **Integration test** for the use case (happy path asserting output *and* a gateway re-read,
    plus one method per error branch, conflicts set up in-test).
13. **Fixtures** — `Fixtures/XFixtures.php`, with `public const string`
    reference keys; the integration test lists the fixture classes it needs and reaches rows
    through `getReference()`, never through a hardcoded id.
14. `make pre-commit` — cs-fix, PHPStan level 8, unit suite. Then the integration suite.

---

## 14. Known inconsistencies in the source codebase, and the ruling adopted here

The codebase this blueprint is extracted from grew through a v0 → v1 migration and carries two
overlapping generations of several patterns. Each of these is called out so a new project starts
with **one** answer. Where the source disagrees with itself, the ruling below is what this document
teaches; revisit any of them if you prefer the other branch.

Rulings marked **✔** were arbitrated explicitly by the team. The others are this document's
defaults and remain open.

**Two deliberate divergences first.** These are not inconsistencies in the source codebase — they
are places where this blueprint knowingly does something else, because that is how new projects
should start:

- **Persisted classes are data models, not entities.** The source codebase puts them in
  `Domain/Entity/` with a bare noun (`Subscription`); this blueprint puts them in
  `Domain/DTO/DataModel/` with a `DataModel` suffix (`SubscriptionDataModel`), alongside
  `DataInput` and `DataOutput`. The word "entity" does not appear in a new project;
  `EntityNotFoundException` becomes `DataModelNotFoundException`.
- **Fixtures are plain PHP.** The source codebase uses `hautelook/alice-bundle` with YAML files
  under `../fixtures/Entity`; this blueprint uses `doctrine/doctrine-fixtures-bundle` with PHP
  fixture classes under `src/Fixtures/` (§10), which is also what lets tests
  stop depending on hardcoded ids (§9.4).

The table below lists only the places where the source codebase holds **two competing answers**. Plain
violations of a rule it already agreed on — Yoda comparisons, missing `final`, a deprecated
`Route` import, a validator carrying a `UseCase` infix — are not listed: §5 and §7 state the rule,
and a project started from this blueprint has nothing to migrate.

| # | Inconsistency observed | Ruling adopted in this blueprint |
| --- | --- | --- |
| 1 | Two repository stacks: `Domain/Repository/*` (Doctrine repositories inside Domain, some marked `@deprecated`, some not) and `Infrastructure/Repository/*` implementing gateways. Two classes even share a name. | **Infrastructure-only.** No repository in `Domain/`. Domain holds gateway interfaces exclusively. |
| 2 | `Domain/DataProvider/*` wrappers around the legacy repositories, all `@deprecated "should be replaced by a gateway"`, still used by 4 commands and 5 use cases. | **Drop the concept.** Gateways only. |
| 3 | Three persister base classes coexist: `AbstractBaseMysqlPersister` (the documented one), `AbstractPersister`, and `DoctrinePersister`/`PersisterInterface` (a generic `add/remove/commit` façade). | **`AbstractBaseMysqlPersister` only.** |
| 4 | Two outbound-integration generations: the documented `GiftCardProviderApiFinder` + `GiftCardProviderApiInterface` + YAML map (all `@deprecated`), and the tagged-locator `ProviderHttpClientInterface` + `…Resolver` (no YAML). The project's own `../CLAUDE.md` still documents the deprecated one. | **Tagged services + locator** (§6.15). |
| 5 | Output DTO suffix: most are `…DataOutput`, but `GiftCardOutput`, `MappingOutput`, `FixedFaceValueOutput`, `CustomFaceValueOutput`, `SimpleReportOutput` are `…Output`. | **✔ `…DataOutput` everywhere**, symmetric with `…DataInput` — nested value objects and generic envelopes included. |
| 6 | Entity→output mapping: some use cases call `$objectMapper->map(...)` directly, others go through an `OutputFactory` that also calls the mapper. | **✔ An `OutputFactory` always** (§6.9), even when trivial. A use case never injects `ObjectMapperInterface`. |
| 7 | Constraints: `NameAvailableConstraint` is static and pure; `ClientValidConstraint` is an injected service that queries a gateway and throws (marked `@deprecated "constraint should be static"`). | **Static, pure, violation-accumulating** (§6.8). |
| 8 | 9 of 19 entities have no `createdAt`/`updatedAt` despite the convention (`Provider`, `GiftCard`, `Client`, `Mapping`…). The abstract persister guards with `property_exists`. | **✔ Every entity declares both**, projections, caches and append-only logs included. Keep the `property_exists` guard as belt-and-braces. |
| 9 | `DataTransformer` exists in both `Domain/` and `Infrastructure/`, with two different date formats (`Y-m-d\TH:i:sP` vs `Y-m-d H:i:s`) — and a `Domain` output DTO imports the `Infrastructure` one, breaking domain purity. | **✔ Both, with disjoint roles** (§6.18): `Domain/DataTransformer` = pure formatters, the only ones a DataOutput may reference; `Infrastructure/DataTransformer` = vendor parsing/sanitising, never imported by Domain. **One API date format: ISO 8601 with offset** (`Y-m-d\TH:i:sP`), held in a single `const`. |
| 10 | Gateway coverage is partial: `GiftCardConfigPersister` and `ProviderMerchantRawDataPersister` implement no gateway and are injected as concrete classes; `GiftCardConfigPersisterGateway` does not exist. Persisters also live both flat and in a `Persister/GiftCard/` subfolder. | **✔ Every persister implements a gateway, without exception** — projections, caches and vendor raw data included, even when only Infrastructure writes to them. Persisters stay flat in `Infrastructure/Persister/`. |
| 11 | Exception placement: `ValidationException` in `Domain/Exception`, `EntityNotFoundException` and `DataInputMappingException` in `Infrastructure/Exception` — and use cases import the infrastructure ones. `EntityNotFoundException` is also constructed both with a class name and with a free-text message. | **✔ Keep the split** (domain rule vs infrastructure failure), documented as the one allowed UseCase→Infrastructure import, and **always construct `EntityNotFoundException` with `Entity::class`**. |
| 12 | `../Makefile` targets are `php-cs-fixer` / `phpstan` (which `.git-hooks/pre-commit` calls correctly), but `../../../CLAUDE.md` and the README both document `make cs-fix` / `make stan`, which do not exist. `reset_db` and `reset-db` both exist with different semantics (the latter runs `--env=dev` inside the *test* container). | **✔ kebab-case everywhere, one name per action, no aliases** (§3.1); the README, the agent-facing doc and the git hook cite only targets that exist, checked in CI. |
| 13 | `UseCase::execute` signatures vary: `execute(XDataInput)`, `execute(int $id, XDataInput)`, `execute(int $id)`, `execute(string $poolId)`, while `../CLAUDE.md` documents only the first. | **✔ All four are legitimate** and documented as such (§6.10): the DataInput carries the body, scalars carry route identity. |
| 14 | `Domain/GiftCard/GiftCardEligibility` is a domain service with no suffix, in a folder shaped unlike the rest of `Domain/`; `Domain/DataProvider/Merchant/ClientDataProvider` sits under a sub-domain folder that does not match its entity. | **✔ Domain services live in `Domain/<SubDomain>/` and stay unsuffixed** — their name says what they do; but the sub-domain folder must match the aggregate they serve. |

---

## 15. Anti-patterns

Rejected in review, every time:

- Injecting `EntityManagerInterface` or a `Repository` outside `Infrastructure/`.
- Calling Doctrine's `find` / `findBy` / `findOneBy` / `findAll` from application code.
- Relying on lazy loading instead of `addSelect`.
- A use case with a second public method.
- Business logic in a controller or in a command.
- A validator that fails fast instead of accumulating.
- A constraint that queries the database.
- `new \DateTimeImmutable()` outside a persister's clock.
- Getters/setters on an entity or a DTO.
- Injecting `ObjectMapperInterface` anywhere but an OutputFactory.
- Importing `Infrastructure\DataTransformer` from `Domain/`.
- A second date format anywhere in the API.
- A backed enum where a `Registry` belongs.
- A Makefile target that exists under two spellings, or a doc citing one that does not exist.
- `if ($x)` / `if ($x === true)` / `if (!$x)`.
- A `services.yaml` alias for a gateway (it means two implementations exist).
- A hand-maintained `match`/YAML map of interchangeable implementations instead of a tag.
- An integration test whose conflict setup lives in a fixture.
- A test that hardcodes a row id instead of going through a fixture reference.
- A YAML fixture file, or any fixture library beyond `doctrine/doctrine-fixtures-bundle`.
- The word "entity" for a persisted class — it is a **data model**, in `Domain/DTO/DataModel/`.
- A new pattern introduced next to an existing one without deprecating the old one.
