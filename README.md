# Rest Client Sdk [![Build Status](https://travis-ci.org/mapado/rest-client-sdk.svg?branch=v0.3.0)](https://travis-ci.org/mapado/rest-client-sdk)
Rest Client SDK for hydra API.

This client tries to avoid the complexity of implementing a custom SDK for every API you have.
You just have to implements your model and a little configuration and it will hide the complexity for you.

## Can I use it in production
We are currently developping it and will use it in production soon. But for now it is a little dangerous to use it in production.
If you want to use it, please report every bug you may find by [opening an issue](https://github.com/mapado/rest-client-sdk/issues/new) or even better, a [Pull Request](https://github.com/mapado/rest-client-sdk/compare).

## installation
```sh
composer require mapado/rest-client-sdk
```

## Usage
Imagine you have those API endpoints:
  * /v2/carts
  * /v2/carts/{id}
  * /v2/cart\_items
  * /v2/cart\_items/{id}

You will need to have two entities, let's say:
  * Foo\Bar\Model\Cart
  * Foo\Bar\Model\CartItem

You will need to declare one `Mapping` containing your two `ClassMetadata`

## Configuration
### Configure an entity
Imagine the following entities:
```php
namespace Acme\Foo\Bar;

use Mapado\RestClientSdk\Mapping\Annotation as Rest;

/**
 * @Rest\Entity(key="carts", client="Acme\Foo\Bar\CartClient")
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

/**
 * @Rest\Entity(key="cart_items", client="Acme\Foo\Bar\CartItemsClient")
 */
class CartItem {
    /**
     * @Rest\Id
     * @Rest\Attribute(name="id", type="string")
     */
    private $id;

    /**
     * @Rest\Attribute(name="number", type="integer")
     */
    private $number;

    /**
     * @Rest\ManyToOne(name="cart", targetEntity="Cart")
     */
    private $cart;
}
```

### Explanations
`Entity` definitions:
  * `key` must be the key of your API endpoint
  * `client` is an empty class extending `Mapado\RestClientSdk\Client\AbstractClient`

Attributes definition:
  * `name` the name of the key in the API return format
  * `type` type of the attribute

Relations definition:
  * `name` the name of the key in the API return format
  * `targetEntity` class name of the target entity

## Declaring the SdkClient
```php
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\RestClient;
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\Mapping\Driver\AnnotationDriver;

$restClient = new RestClient(new GuzzleHttp\Client, 'http://path-to-your-api.root');

$annotationDriver = new AnnotationDriver($cachePath, $debug = true);

$mapping = new Mapping('/v2'); // /v2 is the prefix of your routes
$mapping->setMapping($annotationDriver->loadDirectory($pathToEntityDirectory));

$sdkClient = new SdkClient($restClient, $mapping);
```

## Usage
```php
$cartClient = $sdkClient->getClient('carts');
$cart = $cartClient->find(838); // find cart by id
$cartList = $cartClient->findAll(); // find all carts

$cartItemList = $cart->getCartItemList();

foreach ($cartItemList as $cartItem) {
    echo $cartItemList->getNumber();
}
```

## TODO
  * Symfony bundle
  * YAML declaration on entity / relations
  * Auto-generate empty client classes and make them optional


## Missing tests
```json
{
    "cart": {
        "cartItemList": [
            {
                "@id": "..."
            }
        ],
        "anotherList": [
            {
                "@id": "..."
            }
        ]
    }
}
```

check that `anotherList` metadata does not have the cartItemList metadata
