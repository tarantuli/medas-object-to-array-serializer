# medas-object-to-array-serializer

Part of the [Medas framework](https://github.com/tarantuli/medas-core).

## Description

Bidirectional serializer that converts PHP objects to plain associative arrays and back. It uses reflection to walk an object's full property graph — including nested objects, enums, typed arrays, and generic base classes — without requiring any changes to the classes being serialized beyond optional attributes for fine-tuning behaviour.

**Supported property types:**

- Scalars: `int`, `float`, `string`, `bool`
- `mixed` — passed through as-is
- Nested objects — serialized recursively and reconstructed from declared property types
- `BackedEnum` — serialized to its backing `int`/`string` value, restored with `::from()`
- `UnitEnum` — serialized to its case name, restored via `::case()`
- Arrays comprising any of the above — element type declared via `@var` PHPDoc
- `Closure` — kept as-is in the array; not reconstructed on unserialize

All property visibilities (public, protected, private) and promoted constructor properties are included.

**The constructor is not called during unserialisation** — properties are set directly via reflection. Constructor-side validation is intentionally bypassed.

**Optional attributes:**

| Attribute                         | Target   | Effect                                                                                   |
|-----------------------------------|----------|------------------------------------------------------------------------------------------|
| `#[DontSerializeMe]`              | Property | Excluded from serialization entirely                                                     |
| `#[DontSerializeEmptyValues]`     | Class    | `null`, `[]`, `false`, `0` omitted from output (`''` and `'0'` are kept)                 |
| `#[CastToClassName]`              | Class    | Serializes as the FQCN string; unserializes by instantiating the class with no arguments |
| `#[Handler(HandlerClass::class)]` | Property | Delegates serialize/unserialize to a custom `PropertyHandler` service                    |

## Usage

### Package developer context

Register the package:

```php
use Medas\ObjectToArraySerializer\ObjectToArraySerializerPackage;

ObjectToArraySerializerPackage::instance();
```

**Basic serialize and unserialize:**

```php
use Medas\ObjectToArraySerializer\ObjectToArraySerializer;
use Medas\Core\Attributes\Service;

#[Service]
readonly class DataTransformer
{
    public function __construct(
        private ObjectToArraySerializer $serializer,
    ) {}

    public function toArray(object $object): array
    {
        return $this->serializer->serialize($object);
    }

    public function fromArray(array $data, string $class): object
    {
        return $this->serializer->unserialize($data, class: $class);
    }
}
```

**Nested objects:**

```php
class Address
{
    public string $street;
    public string $city;
}

class Customer
{
    public string  $name;
    public Address $address;
}

$array = $serializer->serialize($customer);
// ['name' => 'Alice', 'address' => ['street' => '...', 'city' => '...']]

$restored = $serializer->unserialize($array, class: Customer::class);
// Customer { $name: 'Alice', $address: Address { ... } }
```

**Enums:**

```php
enum Status: string { case Active = 'active'; case Inactive = 'inactive'; }
enum Priority { case Low; case High; }

class Task
{
    public Status   $status;
    public Priority $priority;
}

$array = $serializer->serialize($task);
// ['status' => 'active', 'priority' => 'High']
```

**Typed arrays — `@var` annotation required:**

```php
class Team
{
    /** @var Member[] */
    public array $members;

    /** @var string[] */
    public array $roles;
}
```

Supported formats: `Member[]`, `array<Member>`, relative names, sub-namespace names (`Sub\Ns\Member[]`), and FQCNs (`\Fully\Qualified\Member[]`). Arrays without a `@var` annotation throw `ArraysMustSpecifyContentType` at unserialize time.

**Generic base classes with template types:**

```php
/** @template T */
abstract class Collection
{
    /** @var array<T> */
    public array $items;
}

/** @extends Collection<Product> */
class ProductCollection extends Collection {}
```

`T` is resolved to `Product` when unserializing a `ProductCollection`. The template type finder walks the class hierarchy to resolve all `@template` / `@extends` declarations.

**`#[DontSerializeMe]` — exclude a property:**

```php
use Medas\ObjectToArraySerializer\DontSerializeMe;

class Session
{
    public string $token;

    #[DontSerializeMe]
    public string $rawPassword = '';
}

// $rawPassword is absent from the array and retains its default on unserialize
```

**`#[DontSerializeEmptyValues]` — omit falsy properties:**

```php
use Medas\ObjectToArraySerializer\DontSerializeEmptyValues;

#[DontSerializeEmptyValues]
class SearchFilter
{
    public string $query     = '';
    public int    $page      = 0;
    public bool   $active    = false;
    public array  $tags      = [];
}

$serializer->serialize(new SearchFilter());
// ['query' => '']  — empty string is kept; 0, false, [] are dropped
```

**`#[CastToClassName]` — serialize as a class name:**

```php
use Medas\ObjectToArraySerializer\SerializeToClassName\CastToClassName;

#[CastToClassName]
abstract class Formatter {}

class JsonFormatter extends Formatter {}
class CsvFormatter  extends Formatter {}

class ReportConfig
{
    public Formatter $formatter;
}

$config = new ReportConfig();
$config->formatter = new CsvFormatter();

$array = $serializer->serialize($config);
// ['formatter' => 'MyApp\Formatters\CsvFormatter']

$restored = $serializer->unserialize($array, class: ReportConfig::class);
// ReportConfig { $formatter: CsvFormatter {} }
```

Classes annotated with `#[CastToClassName]` must have no required constructor arguments.

**`#[Handler]` — custom property handler:**

```php
use Medas\Core\Attributes\{Handler, Service};
use Medas\Core\Interfaces\PropertyHandler;

#[Service]
readonly class CsvHandler implements PropertyHandler
{
    public function serialize(mixed $value): string
    {
        return implode(',', $value);
    }

    public function unserialize(mixed $value): array
    {
        return explode(',', $value);
    }
}

class Report
{
    #[Handler(CsvHandler::class)]
    public array $tags;
}

// ['php', 'oop'] serializes to 'php,oop' and is restored to ['php', 'oop']
```

**Controlling unknown key behaviour:**

```php
use Medas\ObjectToArraySerializer\{ArrayToObjectCaster, ArrayToObjectCaster\Settings};

// By default, unknown array keys are silently skipped.
// Pass skipUnknownValues: false to throw PropertyDoesNotExist instead.
$caster = service(ArrayToObjectCaster::class);
$object = $caster->cast($array, MyClass::class, new Settings(skipUnknownValues: false));
```

### Backend user context

All serialization happens through injected services — there are no CLI commands. The package is most commonly used by `medas-storage-manager` backends and REST response serializers, but it can be injected anywhere an object ↔ array transformation is needed.

**Exception reference:**

| Exception                                              | Thrown when                                                          |
|--------------------------------------------------------|----------------------------------------------------------------------|
| `ValueMustBeObject`                                    | `serialize()` called with a non-object                               |
| `ValueMustBeArray`                                     | `unserialize()` called with a non-array                              |
| `ClassNameMustBeValidAndExisting`                      | `unserialize()` called with a non-existent class name                |
| `PropertyDoesNotExist`                                 | Array key has no matching property (when `skipUnknownValues: false`) |
| `CantCastValueToType`                                  | Value cannot be cast to the declared property type                   |
| `ArraysMustSpecifyContentType`                         | Array property has no `@var` annotation                              |
| `CastingToUnionTypesIsNotImplemented`                  | Property has a union type (e.g. `int\|string`)                       |
| `ClassThatCastsToNameShouldntHaveConstructorArguments` | `#[CastToClassName]` class has required constructor parameters       |
