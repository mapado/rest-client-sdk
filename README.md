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

// Find entity based on ID as defined in the entity by @Rest\Id
$cart = $repository->find(1);

// Find all entities in the database
$cart = $repository->findAll();

// Find one entity based on the fielddefined in the function name (in this case <Name>)
$cart = $repository->findOneByName('username');

// Find one entity based on the criteria defined in the array
$cart = $repository->findOneBy(array('name'=>'username','date'=>'1-1-2016'));

To find all matches for the two examples above replace findOneByName() with findByName() and findOneBy() with findBy()

// Add entity
$cart = new \Acme\Foo\Bar\Cart;
$cart->setName('new name');       
$repository->persist($cart);

// Update entity
$cart = $repository->find(13);
$cart->setDescription('New description');
$repository->update($cart);

// Delete entity
$cart = $repository->find(13);
$repository->remove($cart);

```

