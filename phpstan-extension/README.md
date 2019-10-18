# PHPStan rules for RestClientSdk

This extension provides following features:

- Recognizes magic findBy*, findOneBy* and countBy\* methods on EntityRepository.
- Validates entity fields in repository findBy, findBy*, findOneBy, findOneBy*, count and countBy\* method calls.
- Interprets EntityRepository<MyEntity> correctly in phpDocs for further type inference of methods called on the repository.
- Provides correct return for Doctrine\ORM\EntityManager::getRepository().

## Installation

The package is installed with `mapado/rest-client-sdk`, so you probably have nothing to do if you are here ;)

You need to include the extension in your `phpstan.neon` file:

```neon
includes:
    - vendor/mapado/rest-client-sdk/phpstan-extension/extension.neon
```

## Configuration

As rest-client-sdk works a lot with the Metadata configured in the entities, you may want to leverage advanced analysis by providing the SdkClientRegistry of your application.
This will allow correct entity class when calling `$sdkClient->getRepository('carts')` and when calling magic methods from repositories.

This configuration step is optional but really recommended.

```neon
# phpstan.neon
parameters:
    rest_client_sdk:
        registryFile: tests/rest-client-sdk-registry.php
```

For example, in a Symfony project, `tests/rest-client-sdk-registry.php` would look something like this:

```php
require dirname(__DIR__) . '/../config/bootstrap.php';

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

$kernel->boot();

return $kernel->getContainer()->get('mapado.rest_client_sdk');
```

## Inspiration

This extension is greatly inspired by the awesome work done in [phpstan-doctrine](https://github.com/phpstan/phpstan-doctrine).
