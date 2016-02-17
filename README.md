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

use Mapado\RestClientSdk\Mapping\Annotations as Rest;

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
### Using Symfony ?
There is a bundle to easily integrate this component: [mapado/rest-client-sdk-bundle](https://github.com/mapado/rest-client-sdk-bundle)

### Not using Symfony
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
### Fetching an entity / a list of entities
```php
$cartClient = $sdkClient->getClient('carts');
$cart = $cartClient->find(838); // find cart by id
$cartList = $cartClient->findAll(); // find all carts

$cartItemList = $cart->getCartItemList();

foreach ($cartItemList as $cartItem) {
    echo $cartItemList->getNumber();
}
```

### Creating a new instance
```php
$cart = new Cart();
$cart->setStatus('awaiting_payment');
$cart->setCreatedAt(new \DateTime());

$cartClient = $sdkClient->getClient('carts');
$cart = $cartClient->persist($cart);
```

The `persist` operation will send a `POST` request with the serialized object to the API endpoint and return the newly created object.

### Updating an instance
```php
$cartClient = $sdkClient->getClient('carts');
$cart = $cartClient->find(838); // find cart by id

$cart->setStatus('payed');
$cart = $cartClient->update($cart);
```

The `update` operation will send a `PUT` request with the serialized object to the API endpoint (using the object `id`) and return the updated object.

### Deleting an instance
```php
$cartClient = $sdkClient->getClient('carts');
$cart = $cartClient->find(838); // find cart by id

$cartClient->remove($cart);
```

The `remove` operation will send a `DELETE` request to the API endpoint using the object ID.

### Extending the client
The default client provides the basic CRUD methods (as seen before). But in many case, you will need to extends it to add your own methods.

If you have a `Cart` Model, you defined a `CartClient` extending `Mapado\RestClientSdk\Client\AbstractClient`. The default implementation is an empty class.

You can just define the method you want in this class:
```php
namespace Acme\Foo\Bar;

use Mapado\RestClientSdk\Client\AbstractClient;

class CartClient extends AbstractClient
{
    public function findByCustomer($customer)
    {
        $cartList = $this->restClient->get(sprintf('/v1/carts?customer=%s', $customer->getId())); // this endpoint should return a hydra list
        return $this->convertList($cartList);
    }

    public function findOneByPayment($payment)
    {
        $cart = $this->restClient->get(sprintf('/v1/carts?payment=%s', $payment->getId())); // this endpoint should return an item
        return $this->convert($cart);
    }
}
```

## Want to help ? Found a bug ?
If you want to use it, [pleeeaase](https://s-media-cache-ak0.pinimg.com/736x/4e/94/1c/4e941cc9fea61425f21ed18ebc86d0d7.jpg) report every bug you may find by [opening an issue](https://github.com/mapado/rest-client-sdk/issues/new) or even better, a [Pull Request](https://github.com/mapado/rest-client-sdk/compare).

## TODO
  * Auto-generate empty client classes and make them optional
  * YAML declaration on entity / relations (?)


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
