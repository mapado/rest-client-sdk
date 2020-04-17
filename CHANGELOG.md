# Changelog

## 0.33.3

### Fixed

Fix issue when `content-type` header is in lowercase

## 0.33.2

### Added

Removed default registryFile

## 0.33.1

### Added

Added phpstan extension

## 0.33.0

### Added

Added a `Mapado\RestClientSdk\SdkClientRegistry` to facilitate SdkClient selection when we don't really care about which Client manages our entity.
This is totally inspired by Doctrine Persistence ManagerRegistry.

## 0.32.2

### Fixed

- Fixed issue with Symfony < 3.1

## 0.32.1

### Added

- Allow `datetime` type to be either a `DateTime` or a `DateTimeImmutable` instance.

### Fixed

- Removed a `var_dump` introduced in `0.32.0`

## 0.32.0 [BROKEN]

- Leverage [Symfony Property Access](https://symfony.com/doc/current/components/property_access.html) component instead of homemade `$setter` and `$getter` methods (#92)
- Throw an exception if the `setter` method does not exists on entity (#91)
- `RestException` now has a `getRequest(): ?\Psr\Http\Message\RequestInterface` function

## 0.31.1

### Changed

Fixes #90 (allow int as id in model).

## 0.31.0

### Changed

- [MINOR BC] `Mapado\RestClientSdk\RestClient::getCurrentRequest(): Request` changed to `Mapado\RestClientSdk\RestClient::getCurrentRequest(): ?Request`. The call can now return null if we are in a `cli` execution mode.
  It will not break if you do not extend the `RestClient` class (the function is protected).

## 0.30.0

### Changed

- [BREAKING] Drop support for PHP < 7.2
- [BREAKING] Use type hinting for return types and parameter everywhere
- [MIGHT BREAK] use `declare(strict_types)` everywhere
- [MIGHT BREAK] the identifier attribute (`@Rest\Id`) is mandatory to call `ClassMetadata::getIdentifierAttribute()`
  It previously defaulted to `id`. It was more to avoid BC break in Mapado codebase more than a real feature.
  It now throws an instance of [MissingIdentifierException](https://github.com/mapado/rest-client-sdk/blob/v0.30.0/src/Exception/MissingIdentifierException.php)
  This way is is less magic and more understandable.
- [MIGHT BREAK] Throw a lot more exception than before in different case that should not really happen (like calling `getMetadata` with a wrong model name for example).
- Throw an exception if two identifier attributes are set for an entity

## 0.29.4

### Changed

- Fix issue with `UnitOfWork::registerClean` when we call `EntityRepository::find()`

## 0.29.3

### Changed

Fix issue with return type introduced in 0.29.2

## 0.29.2

### Changed

- log response in case of `RequestException`

## 0.29.1

### Changed

- `$unitOfWork` in SdkClient constructor is optionnal

## 0.29.0

### Changed

- [Might break] The deserialization process (response → object) now accept object without an identifier.
  These object might be partial object OR entity without an identifier attribute at all

## 0.28.2

### Added

- Use php-cs-fixer and prettier-php to format code

### Changed

- Fix issue when calling proxy id from twig which return an empty id instead of the real id

## 0.28.1

### Changed

- fix issue with call on isset with id

## 0.28.0

### Changed

- Upgrade to [ProxyManager](https://packagist.org/packages/ocramius/proxy-manager) `^2.0`
- [Might Break] Remove dependencies on `misd/phone-number-bundle`. If you used this, you need to either:
  - Set the dependency in your `composer.json` file,
  - Or set the dependency on `giggsey/libphonenumber-for-php` only (as the bundle is not used in this package)

## 0.27.0

### Changed

- [Breaking] Drop support for PHP < 7.1
- Use PHPStan to detect errors

## 0.26.2

### Changed

- Fix issue with count and PHP 7.2
- Drop support for PHP < 7.0

## 0.26.1

### Changed

allow calling magic method `isset` on id

## 0.26.0

### Added / Maybe breaking

- persisting an entity now leverage the power of the `UnitOfWork` and only `POST` data that are not null. [See #68](https://github.com/mapado/rest-client-sdk/pull/68)

## 0.25.2

### Changed

- Avoid notice for new entity in `UnitOfWork` #4eee2ba

## 0.25.1

### Changed

- ignore backtrace args to save memory

## 0.25.0

### Added

- Add the backtrace in the log

### Fix

- fix notice in `UnitOfWork`

## 0.24.0

### Fix / Maybe breaking

- The UnitOfWork now treat array (without Relation) as full object and does not make a `diff` on them

## 0.23.0

### Breaking change

- add Mapado\RestClientSdk\UnitOfWork in constructor of Mapado\RestClientSdk\EntityRepository

## 0.22.0

### Features

- Add current request uri as referer [#56](https://github.com/mapado/rest-client-sdk/pull/59)

### May be breaking

- This package requires `symfony/http-foundation: ^2.7||^3.0`

## 0.21.0

### Features

- Added support for abstract entities [#56](https://github.com/mapado/rest-client-sdk/pull/56)

## 0.20.0

### Breaking change

- `Mapado\RestClientSdk\Collection\HydraCollection` does not exists anymore and is replaced by `Mapado\RestClientSdk\Collection\Collection`

  - It is now a simple collection that could contain anything

- Some internal breaking changes that might affect you:
  - `Mapado\RestClientSdk\Collection\Collection` constructor forbid using anything else than an array as a first argument
  - `Model\ModelHydrator::deserializeAll` is now private

### Features

- `Collection` objects now accept an `extraProperties` parameter as second argument
- HAL JSON response should now be supported
- Mapping now accept a configuration array. This array can take a 'collectionKey' key to set the list default JSON key (default `hydra:member`). The key can contain dots for sublevel.

## 0.19.0

### Breaking change

- When calling a GET query (mainly with `find` / `findBy` or `findAll`), every 4xx response status code except 404 will now throw a `Mapado\RestClientSdk\Exception\RestClientException` (404 will still return `null` value). See [#35](https://github.com/mapado/rest-client-sdk/pull/35/files) and [c9066a8](https://github.com/mapado/rest-client-sdk/commit/c9066a8c18ff1b2bbce3e230a6517ce5d9c5dd19)

### Features

- Change method visibility from private to protected:
  - `fetchFromCache`
  - `saveToCache`
  - `removeFromCache`
  - `addQueryParameter`

## 0.18.0

### Breaking change

- `@id` key is not automatically serialized when the attribute name is `id`, you must set `@Attribute(name="@id")` in your relation (or `new Attribute('@id', 'id')`)
- @Rest\Id is mandatory
- `Mapado\RestClientSdk\Mapping\Attribute` constructor now takes the parameter name as second parameter

### Bugfix

- Fix bug when attribute name and `@Attribute(name="foo")` is not the same ([#13](https://github.com/mapado/rest-client-sdk/issues/13))

## 0.8.0

- Replace the `AbstractClient` by the `EntityRepository`
- remove the `client` parameter to the Annotation, replaced by `repository` if needed
- The `getClient` method from the `SdkClient` is removed and replaced by the `getRepository` method
  - The `getRepository` method accepts the classname of the Model or the key specified in the mapping
