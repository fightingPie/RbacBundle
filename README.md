# RbacBundle

## Principle explanation:

- **English document**: [en.md](./doc/en/symfony-rbac.md)

- **Chinese documents**:[cn.md](./doc/cn/symfony-rbac.md)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
php composer.phar require symfony-bundle/symfony-rbac "*"
```

or add

```json
"symfony-bundle/symfony-rbac": "*"
```
to the require section of your composer.json.

Usage
------------
Once the extension is installed, simply modify your application configuration as follows:

### [Add Routing](https://symfony.com/doc/4.4/routing/custom_route_loader.html)



```yaml
#app/config/routes.yml

app_file:
    # loads routes from the given routing file stored in some bundle
    resource: '@SymfonyRbacBundle/Resources/config/routing/routes.yaml'

app_annotations:
    # loads routes from the PHP annotations of the controllers found in that directory
    resource: '@SymfonyRbacBundle/Controller/'
    type:     annotation

app_bundle:
    # loads routes from the YAML, XML or PHP files found in some bundle directory
    resource: '@SymfonyRbacBundle/Resources/config/routing/'
    type:     directory
```

### Rbac Database's configuration (ex)
 [file of rbac-sql](doc/sql/symfony_rbac_202011160954.sql)
```yaml
#app/config/packages/doctrine.yaml

doctrine:
    dbal:
        default_connection: default
        connections:
            default:
              .....
            rbac:
                driver: 'pdo_mysql'
                url: '%env(resolve:DATABASE_RBAC_URL)%'
        # IMPORTANT: You MUST configure your server version,
        # either here or in the DATABASE_URL env var (see .env file)
        #server_version: '5.7'
    orm:
        default_entity_manager: default
        entity_managers:
            default:
              .....
            rbac:
                connection: rbac
                mappings:
                    RbacBundle:
                        is_bundle: true
                        type: annotation
                        dir: 'Entity'
                        prefix: 'SymfonyBundle\SymfonyRbac\Entity'
                        alias: Rbac
```

### Code

```php
  
  use SymfonyRbac\Services\RbacManager;

  private $rbacManager;

  public function __construct(RbacManager $rbacManager) {
    $this->rbacManager = $rbacManager;
    $userId = 31;
    $permissionName = '/admin/user/31';
    $res =  $this->rbacManager->checkAccess($userId,$permissionName,[]);
  }
```



