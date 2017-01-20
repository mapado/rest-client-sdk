Changelog
======================

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
