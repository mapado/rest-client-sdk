Changelog
======================

## 0.28.0
### Changed
  * Upgrade to [ProxyManager](https://packagist.org/packages/ocramius/proxy-manager) `^2.0`
  * [Might Break] Remove dependencies on `misd/phone-number-bundle`. If you used this, you need to either: 
    * Set the dependency in your `composer.json` file,
    * Or set the dependency on `giggsey/libphonenumber-for-php` only (as the bundle is not used in this package)

## 0.27.0
### Changed
  * [Breaking] Drop support for PHP < 7.1
  * Use PHPStan to detect errors

## 0.26.2
### Changed
  * Fix issue with count and PHP 7.2
  * Drop support for PHP < 7.0

## 0.26.1
### Changed
   allow calling magic method `isset` on id

## 0.26.0
### Added / Maybe breaking
  * persisting an entity now leverage the power of the `UnitOfWork` and only `POST` data that are not null. [See #68](https://github.com/mapado/rest-client-sdk/pull/68)

## 0.25.2
### Changed
  * Avoid notice for new entity in `UnitOfWork` #4eee2ba

## 0.25.1
### Changed
  * ignore backtrace args to save memory

## 0.25.0
### Added
  * Add the backtrace in the log

### Fix 
  * fix notice in `UnitOfWork`

## 0.24.0
### Fix / Maybe breaking
  * The UnitOfWork now treat array (without Relation) as full object and does not make a `diff` on them

## 0.23.0
### Breaking change
  * add Mapado\RestClientSdk\UnitOfWork in constructor of Mapado\RestClientSdk\EntityRepository

## 0.22.0
### Features
  * Add current request uri as referer [#56](https://github.com/mapado/rest-client-sdk/pull/59)

### May be breaking
  * This package requires `symfony/http-foundation: ^2.7||^3.0`

## 0.21.0
### Features
  * Added support for abstract entities [#56](https://github.com/mapado/rest-client-sdk/pull/56)


## 0.20.0
### Breaking change
  * `Mapado\RestClientSdk\Collection\HydraCollection` does not exists anymore and is replaced by `Mapado\RestClientSdk\Collection\Collection`
    * It is now a simple collection that could contain anything

  * Some internal breaking changes that might affect you:
    * `Mapado\RestClientSdk\Collection\Collection` constructor forbid using anything else than an array as a first argument
    * `Model\ModelHydrator::deserializeAll` is now private

### Features
  * `Collection` objects now accept an `extraProperties` parameter as second argument
  * HAL JSON response should now be supported
  * Mapping now accept a configuration array. This array can take a 'collectionKey' key to set the list default JSON key (default `hydra:member`). The key can contain dots for sublevel.

## 0.19.0
### Breaking change
  * When calling a GET query (mainly with `find` / `findBy` or `findAll`), every 4xx response status code except 404 will now throw a `Mapado\RestClientSdk\Exception\RestClientException` (404 will still return `null` value). See [#35](https://github.com/mapado/rest-client-sdk/pull/35/files) and [c9066a8](https://github.com/mapado/rest-client-sdk/commit/c9066a8c18ff1b2bbce3e230a6517ce5d9c5dd19)

### Features
  * Change method visibility from private to protected:
    * `fetchFromCache`
    * `saveToCache`
    * `removeFromCache`
    * `addQueryParameter`


## 0.18.0
### Breaking change
  * `@id` key is not automatically serialized when the attribute name is `id`, you must set `@Attribute(name="@id")` in your relation (or `new Attribute('@id', 'id')`)
  * @Rest\Id is mandatory
  * `Mapado\RestClientSdk\Mapping\Attribute` constructor now takes the parameter name as second parameter

### Bugfix
  * Fix bug when attribute name and `@Attribute(name="foo")` is not the same ([#13](https://github.com/mapado/rest-client-sdk/issues/13))

## 0.8.0
* Replace the `AbstractClient` by the `EntityRepository`
* remove the `client` parameter to the Annotation, replaced by `repository` if needed
* The `getClient` method from the `SdkClient` is removed and replaced by the `getRepository` method
  * The `getRepository` method accepts the classname of the Model or the key specified in the mapping
