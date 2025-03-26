# Fastfony license checker bundle

## Install

```sh
composer require fastfony/license-bundle
```

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    Fastfony\LicenseBundle\FastfonyLicenseBundle::class => ['all' => true],
];
```

Create a `config/packages/fastfony_license.yaml` file:

```yaml
fastfony_license:
    key: '%env(database:FASTFONY_LICENSE_KEY)%'
```
