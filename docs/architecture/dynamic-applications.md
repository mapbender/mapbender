# Dynamic Applications

Mapbender applications are loaded using their slug, a string that uniquely identifies an app no matter where it's loaded from. By default, mapbender supports loading applications from a database using doctrine, or from a yaml definition.

If your app supports applications from another source, you need to overwrite the service `Mapbender\CoreBundle\Component\Application\ApplicationResolver` (alias: `mapbender.application.resolver`). It defaults to `Mapbender\CoreBundle\Component\Application\DbAndYamlApplicationResolver`, but can be easily overridden by either changing the parameter `mapbender.application.resolver.class` or creating a new service that aliases to `Mapbender\CoreBundle\Component\Application\ApplicationResolver`, e.g.

```php
#[AsAlias(ApplicationResolver::class)]
class CustomApplicationResolver extends DbAndYamlApplicationResolver
{
    public function __construct(
        #[Autowire(service: 'mapbender.application.yaml_entity_repository')] ApplicationYAMLMapper $yamlRepository,
        EntityManagerInterface $em,
        AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct($yamlRepository, $em, $authorizationChecker);
    }

    public function getApplicationEntity(string $slug): Application
    {
        // Your custom logic here
        return parent::getApplicationEntity($slug);
    } 
}
```


[↑ Back to top](#dynamic-applications)

[← Back to README](../README.md)
