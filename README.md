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
  * /v2/cart_items
  * /v2/cart_items/{id}

You will need to have two entities, let's say:
  * Foo\Bar\Model\Cart
  * Foo\Bar\Model\CartItem

You will need to declare one `Mapping` containing your two `ClassMetadata`

## Entity declarations
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

/**
 * @Rest\Entity(key="cart_items")
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

Attributes definition:
  * `name` the name of the key in the API return format
  * `type` type of the attribute

Relations definition:
  * `name` the name of the key in the API return format
  * `targetEntity` class name of the target entity

## Configuration
### Using Symfony ?
There is a bundle to easily integrate this component: [mapado/rest-client-sdk-bundle](https://github.com/mapado/rest-client-sdk-bundle).

Once configured, you can get a client like this:
```php
$sdkClient = $this->get('mapado.rest_client_sdk.foo');
```

### Not using Symfony
You need to configure client this way:
```php
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\RestClient
use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\Mapping\Driver\AnnotationDriver;

$restClient = new RestClient(new GuzzleHttp\Client, 'http://path-to-your-api.root');

$annotationDriver = new AnnotationDriver($cachePath, $debug = true);

$mapping = new Mapping('/v2'); // /v2 is the prefix of your routes
$mapping->setMapping($annotationDriver->loadDirectory($pathToEntityDirectory));

$sdkClient = new SdkClient($restClient, $mapping);
```

## Accessing data
### Fetching an entity / a list of entities
```php
$repository = $sdkClient->getRepository('Acme\Foo\Bar\Cart');

// Find entity based on ID as defined in the entity by @Rest\Id
$cart = $repository->find(1);

// Find all entities in the database
$cart = $repository->findAll();

// Find one entity based on the fielddefined in the function name (in this case <Name>)
$cart = $repository->findOneByName('username');

// Find one entity based on the criteria defined in the array
$cart = $repository->findOneBy(array('name'=>'username','date'=>'1-1-2016'));

To find all matches for the two examples above replace findOneByName() with findByName() and findOneBy() with findBy()
```

### Creating a new instance
```php
$cart = new \Acme\Foo\Bar\Cart;
$cart->setStatus('awaiting_payment');
$cart->setCreatedAt(new \DateTime());
$repository->persist($cart);
```

The `persist` operation will send a `POST` request with the serialized object to the API endpoint and return the newly created object

### Updating an instance
```php
$cart = $repository->find(13);
$cart->setStatus('payed');
$repository->update($cart);
```

The `update` operation will send a `PUT` request with the serialized object to the API endpoint (using the object `id`) and return the updated object.

### Deleting an instance
```php
$cart = $repository->find(13);
$repository->remove($cart);
```

The `remove` operation will send a `DELETE` request to the API endpoint using the object ID.

### Extending the repository

If you need to extend the [EntityRepository](https://github.com/mapado/rest-client-sdk/blob/master/src/EntityRepository.php), you can just do something like that:

```php
use \Mapado\RestClientSdk\EntityRepository;

class CartRepository extends EntityRepository
{
    public function findOneByFoo($bar) {
        // generate the path to call
        $path = // ...
        $data = $this->restClient->get($path);
        return $this->sdk->getModelHydrator()->hydrate($data, $this->entityName); // hydrate for an entity, hydrateList for a list
    }
}
```
