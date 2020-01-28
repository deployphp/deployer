# Contributing

Thank you for considering contributing to Deployer. Please make sure to read the following sections if you plan on submitting new issues or pull requests.

## Reporting bugs

In order for us to provide you with help as fast as possible, please make sure to include the following when reporting bugs.

* Deployer version
* PHP version
* Deployment target(s) OS
* Content of `deploy.php`
* Output log with enabled option for verbose output `-vvv`

Please check [existing issues](https://github.com/deployphp/deployer/issues) before opening a new issue, to prevent duplication. You can [open a new issue here](https://github.com/deployphp/deployer/issues/new).

## Contributing code

Fork the project, create a feature branch, and send a pull request.

If you would like to help take a look at the [list of issues](https://github.com/deployphp/deployer/issues).

### Code quality

To ensure a consistent code base, please make sure the code follows
the [PSR-1 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md).

### Update the changelog

Before submitting code make sure to add notes about your changes to [CHANGELOG.md](https://github.com/deployphp/deployer/blob/master/CHANGELOG.md).

To ensure consistent formatting and avoid test failures it's best to do this from the command line. From the project root first install composer dependencies (`composer install`), then run `php bin/changelog`.

### Submit a pull request

All code contributions must go through a pull request and be approved by a core developer before being merged. This is to ensure proper review of all the code.
