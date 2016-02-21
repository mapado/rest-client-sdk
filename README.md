# Work in progress to test a repository solution - not finished code - do not use

# Rest Client Sdk [![Build Status](https://travis-ci.org/mapado/rest-client-sdk.svg?branch=v0.7.0)](https://travis-ci.org/mapado/rest-client-sdk)
Rest Client SDK for hydra API.

This client tries to avoid the complexity of implementing a custom SDK for every API you have.
You just have to implements your model and a little configuration and it will hide the complexity for you.

## installation
```sh
composer require mapado/rest-client-sdk
```

## Usage
Imagine you have those API endpoints:
  * /v2/carts
  * /v2/carts/{id}

You will need to have an entities, let's say:
  * Foo\Bar\Model\Cart

You will need to declare one `Mapping` containing your `ClassMetadata`

## Configuration
### Configure an entity
Imagine the following entities:
```php
namespace Acme\Foo\Bar;

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

/**
 * @Rest\Entity(key="carts")
 */
class Cart {
    /**
     * @Rest\Id
     * @Rest\Attribute(name="id", type="string")
     */
    private $id;

    /**
     * @Rest\Attribute(name="status", type="string")
     */
    private $status;

    /**
     * @Rest\Attribute(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @Rest\OneToMany(name="cart_items", targetEntity="CartItem")
     */
    private $cartItemList;

    // getters & setters ...
}

```

### Explanations
`Entity` definitions:
  * `key` must be the key of your API endpoint

Attributes definition:
  * `name` the name of the key in the API return format
  * `type` type of the attribute

## Declaring the SdkClient
### Using Symfony ?
There is a bundle to easily integrate this component: [mapado/rest-client-sdk-bundle](https://github.com/mapado/rest-client-sdk-bundle)

## Usage for Symfony
### Fetching an entity / a list of entities
```php
$client = $this->get('mapado.rest_client_sdk.foo'); // 
$repository = $client->getRepository('Acme\Foo\Bar\Cart');
$cart = $repository->find(1);
```

