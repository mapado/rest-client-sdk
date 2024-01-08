## How to migrate from v0 to v1

### Attributes

- Search and replace all occurences of `Mapado\RestClientSdk\Mapping\Annotations` to `Mapado\RestClientSdk\Mapping\Attributes`
- Replace annotations by attributes using a tool like [rector/rector](https://github.com/rectorphp/rector) or manually