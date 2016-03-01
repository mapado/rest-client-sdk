Changelog
======================

## 0.8.0
* Replace the `AbstractClient` by the `EntityRepository`
* remove the `client` parameter to the Annotation, replaced by `repository` if needed
* The `getClient` method from the `SdkClient` is removed and replaced by the `getRepository` method
  * The `getRepository` method accepts the classname of the Model or the key specified in the mapping
