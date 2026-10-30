# EPM

EPM is a PHP library for building reusable query objects for WordPress.

## Tests

The PHPUnit test suite is maintained in this repository and runs automatically on pull requests and pushes to `main`.

To run the tests locally, download the PHPUnit PHAR matching your PHP version and run:

```bash
php phpunit.phar --configuration phpunit.xml
```

| PHP | PHPUnit |
|---|---|
| 8.0 | 9 |
| 8.1 | 10 |
| 8.2 | 11 |
| 8.3 | 12 |
| 8.4–8.5 | 13 |

The CI workflow tests every supported PHP version listed above.
