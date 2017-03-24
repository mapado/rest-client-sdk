Changelog
======================

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
