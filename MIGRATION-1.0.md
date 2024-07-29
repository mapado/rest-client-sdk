## How to migrate from v0 to v1

### Using rector

The followings rector line configs

1. Install `rector/rector` if not already done (init file by launching `vendor/bin/rector process`) : [see how to install](https://getrector.com/documentation)
2. Edit your `rector.php` file by adding this :
```php
    $rectorConfig->ruleWithConfiguration(\Rector\Php80\Rector\Class_\AnnotationToAttributeRector::class, [
        new \Rector\Php80\ValueObject\AnnotationToAttribute('Mapado\\RestClientSdk\\Mapping\\Annotations\\Id', 'Mapado\\RestClientSdk\\Mapping\\Attributes\\Id'),
        new \Rector\Php80\ValueObject\AnnotationToAttribute('Mapado\\RestClientSdk\\Mapping\\Annotations\\Entity', 'Mapado\\RestClientSdk\\Mapping\\Attributes\\Entity'),
        new \Rector\Php80\ValueObject\AnnotationToAttribute('Mapado\\RestClientSdk\\Mapping\\Annotations\\Attribute', 'Mapado\\RestClientSdk\\Mapping\\Attributes\\Attribute'),
        new \Rector\Php80\ValueObject\AnnotationToAttribute('Mapado\\RestClientSdk\\Mapping\\Annotations\\ManyToOne', 'Mapado\\RestClientSdk\\Mapping\\Attributes\\ManyToOne'),
        new \Rector\Php80\ValueObject\AnnotationToAttribute('Mapado\\RestClientSdk\\Mapping\\Annotations\\OneToMany', 'Mapado\\RestClientSdk\\Mapping\\Attributes\\OneToMany'),
    ]);

    $rectorConfig->rule(\Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
```
3. Launch `vendor/bin/rector`

### Manually

1. Replace namespace
```diff
- use Mapado\RestClientSdk\Mapping\Annotations as Rest;
+ use Mapado\RestClientSdk\Mapping\Attributes as Rest;
```

2. Replace annotations by attributes

Example :
```diff
- /**
- * @Rest\Attribute(name="id", type="string")
- /
+ #[Rest\Attribute(name: 'id', type: 'string')]
  private $var;
```

3. Replace repositories class name in `Entity` attribute by class constants

```diff
+ use Mapado\Component\TicketingModel\Model\Repository\ProductRepository;

- #[Rest\Entity(key: 'products', repository:' Mapado\Component\TicketingModel\Model\Repository\ProductRepository')]
+ #[Rest\Entity(key: 'users', repository: ProductRepository::class)]
  class Product {

  }
```
