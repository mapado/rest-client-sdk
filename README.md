# Rest Client Sdk
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
## Declaring a class metadata
```php
use Mapado\RestClientSdk\Mapping\Attribute;
use Mapado\RestClientSdk\Mapping\ClassMetadata;
use Mapado\RestClientSdk\Mapping\Relation;

$cartMetadata = new ClassMetadata(
    'carts', // the key of your api endpoint
    'Foo\Bar\Model\Cart', // the classname of your model
    'Foo\Bar\Client\CartClient' // an empty class extending Mapado\RestClientSdk\Client\AbstractClient
);
$cartMetadata->setAttributeList([
    new Attribute('id', 'string', true), // true is for the primary identifier
    new Attribute('status', 'string'),
    new Attribute('createdAt', 'datetime'),
]);
$cartMetadata->setRelationList'([
    new Relation('cartItemList', Relation::ONE_TO_MANY, 'Foo\Bar\Model\CartItem'),
]);

$cartItemMetadata = new ClassMetadata('cart_items', 'Foo\Bar\Model\CartItem', 'Foo\Bar\Client\CartItemClient');
$cartItemMetadata->setAttributeList([
    new Attribute('id', 'string', true),
    new Attribute('number', 'integer'),
]);
$cartItemMetadata->setRelationList'([
    new Relation('cart', Relation::MANY_TO_ONE, 'Foo\Bar\Model\Cart'),
]);
```

## Declaring the SdkClient
```php
use Mapado\RestClientSdk\Mapping;
use Mapado\RestClientSdk\RestClient;
use Mapado\RestClientSdk\SdkClient;

$restClient = new RestClientSdk(new GuzzleHttp\Client, 'http://path-to-your-api.root');

$mapping = new Mapado\RestClientSdk\Mapping('/v2'); // /v2 is the prefix of your routes
$mapping->setMapping([$cartMetadata, $cartItemMetadata]);

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
  * Annotation system for entity / relations declarations
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
