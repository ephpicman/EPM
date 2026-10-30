# EPM

EPM is a PHP library for building reusable query objects for WordPress.

## Tests

The PHPUnit test suite is maintained in this repository and runs automatically on pull requests and pushes to `main`.

To run the tests locally:

```bash
curl --fail --location --silent --show-error https://phar.phpunit.de/phpunit-13.phar --output phpunit.phar
php phpunit.phar --configuration phpunit.xml
```

The CI workflow tests PHP 8.0 through PHP 8.5 using the compatible PHPUnit major version for each PHP version.
