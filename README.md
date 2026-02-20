# medas-object-to-array-serializer

Part of the [Medas framework](https://github.com/tarantuli/medas-core).

Bidirectional serializer that converts PHP objects to plain arrays and back. It uses reflection to walk an object's full property graph — including nested objects, enums, and typed arrays — without requiring any changes to your classes beyond optional attributes for fine-tuning behaviour.

## Requirements

- PHP 8.4+
- `morphp/medas-core` ^2
- `morphp/medas-php-class-analysis` ^2

## Installation

```bash
composer require morphp/medas-object-to-array-serializer
```

Register the package with your service container:

```php
use Medas\ObjectToArraySerializer\ObjectToArraySerializerPackage;

ObjectToArraySerializerPackage::instance()->boot();
```

## Basic usage

Retrieve `ObjectToArraySerializer` from the service container and call `serialize` / `unserialize`:

```php
use Medas\ObjectToArraySerializer\ObjectToArraySerializer;

$serializer = service(ObjectToArraySerializer::class);

// Object → array
$array = $serializer->serialize($object);

// Array → object
$object = $serializer->unserialize($array, class: MyClass::class);
```

`serialize` accepts any object and returns a flat or nested associative array. `unserialize` reconstructs the object using reflection — the constructor is **not** called, so constructor-side validation is bypassed by design.

## Supported property types

The serializer handles all property visibilities (public, protected, private) and all of the following types:

- Scalar types: `int`, `float`, `string`, `bool`
- `mixed` — passed through as-is in both directions
- Nested objects — serialized recursively into nested arrays and reconstructed on the way back
- `BackedEnum` — serialized to its backing value (`int` or `string`), restored with `::from()`
- `UnitEnum` — serialized to its case name, restored with `::case()`
- Arrays of any of the above — see [Typed arrays](#typed-arrays) below
- `Closure` — serialized as-is (closures are kept in the array but not reconstructed on unserialize)

### Example: basic class

```php
class User
{
    public int $id;
    public string $name;
}

$user = new User();
$user->id = 1;
$user->name = 'Alice';

$array = $serializer->serialize($user);
// ['id' => 1, 'name' => 'Alice']

$restored = $serializer->unserialize($array, class: User::class);
// User { $id: 1, $name: 'Alice' }
```

### Example: promoted and elevated properties

Promoted constructor properties and properties of any visibility are all included:

```php
class Point
{
    public function __construct(
        public int     $x,
        protected int  $y,
        private int    $z,
    ) {}
}

$array = $serializer->serialize(new Point(1, 2, 3));
// ['x' => 1, 'y' => 2, 'z' => 3]
```

### Example: nested objects

```php
class Order
{
    public Customer $customer;
    public Address  $shippingAddress;
}
```

Nested objects are serialized recursively and reconstructed automatically based on the property's declared type.

### Example: enums

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

## Typed arrays

For array properties, the serializer needs to know the element type. You must declare it using a `@var` PHPDoc annotation — both short and generic forms are supported:

```php
class Team
{
    /** @var Member[] */
    public array $members;
}
```

```php
class Team
{
    /** @var array<Member> */
    public array $members;
}
```

The class reference in `@var` can be a short name (resolved via the file's `use` statements), a relative name within the same namespace, or a fully qualified name:

```php
/** @var Member[] */                                      // relative / imported
/** @var Sub\Namespace\Member[] */                        // relative with sub-namespace
/** @var \Fully\Qualified\Member[] */                     // absolute FQCN
```

Arrays of internal PHP types (`string[]`, `int[]`, etc.) are also supported and require no special treatment beyond the `@var` annotation.

> **Note:** An array property without a `@var` annotation will throw `ArraysMustSpecifyContentType` at unserialize time.

### Generic base classes

Template types defined via PHPDoc are resolved through the class hierarchy:

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

When unserializing a `ProductCollection`, the `T` placeholder is resolved to `Product` and each element in `$items` is cast accordingly.

## Attributes

### `#[DontSerializeMe]`

Marks a property to be excluded from serialization entirely. The property will not appear in the output array and will retain its default value after unserialization.

```php
use Medas\ObjectToArraySerializer\DontSerializeMe;

class Session
{
    public string $token;

    #[DontSerializeMe]
    public string $rawPassword = '';
}
```

### `#[DontSerializeEmptyValues]`

Applied to a class. Properties whose value is considered empty (`null`, `[]`, `false`, `0`) will be omitted from the serialized array. The string `'0'` and empty string `''` are **not** considered empty and will always be included.

```php
use Medas\ObjectToArraySerializer\DontSerializeEmptyValues;

#[DontSerializeEmptyValues]
class SearchFilter
{
    public string $query     = '';
    public int    $page      = 0;
    public bool   $published = false;
    public array  $tags      = [];
}

$serializer->serialize(new SearchFilter());
// ['query' => '']  — only the empty string survives; 0, false, [] are dropped
```

### `#[CastToClassName]`

Applied to a class (inherited by subclasses). Instead of serializing the object's properties, the serializer stores only the fully-qualified class name as a string. On unserialize, the class is instantiated with no constructor arguments.

This is useful for representing type information rather than data — for example, a strategy or handler class stored in a configuration object.

```php
use Medas\ObjectToArraySerializer\SerializeToClassName\CastToClassName;

#[CastToClassName]
class JsonFormatter {}

class ExcelFormatter extends JsonFormatter {}  // also cast to class name

class ReportConfig
{
    public JsonFormatter $formatter;
}

$config = new ReportConfig();
$config->formatter = new ExcelFormatter();

$array = $serializer->serialize($config);
// ['formatter' => 'App\Formatters\ExcelFormatter']

$restored = $serializer->unserialize($array, class: ReportConfig::class);
// ReportConfig { $formatter: ExcelFormatter {} }
```

> **Note:** Classes marked with `#[CastToClassName]` must have no required constructor arguments.

### `#[Handler]`

Applied to a property. Delegates serialization and unserialization of that property to a custom `PropertyHandler` service. Useful for types that need non-trivial transformation (e.g. packing an array into a comma-separated string).

```php
use Medas\Core\Attributes\Handler;
use Medas\Core\Interfaces\PropertyHandler;
use Medas\Core\Attributes\Service;

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
```

`$tags = ['php', 'oop']` serializes to `'php,oop'` and is restored to `['php', 'oop']` on unserialize.

## Error handling

All errors throw typed exceptions from the `Medas\ObjectToArraySerializer\Exceptions` namespace:

| Exception | Thrown when |
|---|---|
| `ValueMustBeObject` | `serialize()` is called with a non-object |
| `ValueMustBeArray` | `unserialize()` is called with a non-array |
| `ClassNameMustBeString` | `unserialize()` is called without a valid existing class name |
| `PropertyDoesNotExist` | The array contains a key that doesn't match any property (when `skipUnknownValues` is `false`) |
| `CantCastValueToType` | A value cannot be cast to the declared property type |
| `ArraysMustSpecifyContentType` | An array property has no `@var` type annotation |
| `ClassThatCastsToNameShouldntHaveConstructorArguments` | A `#[CastToClassName]` class has required constructor parameters |

By default, unknown keys in the input array are silently skipped. To throw instead, pass a custom `Settings` object:

```php
use Medas\ObjectToArraySerializer\ArrayToObjectCaster;
use Medas\ObjectToArraySerializer\ArrayToObjectCaster\Settings;

$caster = service(ArrayToObjectCaster::class);
$object = $caster->cast($array, MyClass::class, new Settings(skipUnknownValues: false));
```
