# nielslange/woo-test-environment

WP-CLI command to spin up a [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/) testing environment.

Quick links: [Installing](#installing) | [Usage](#usage) | [Contributing](#contributing)

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with following command.

```sh
wp package install git@github.com:nielslange/woo-test-environment.git
```

## Usage

```sh
wp woo-test-environment setup
```

```sh
wp woo-test-environment teardown
```

### Parameter

The command `wp woo-test-environment setup` can accept the following optional parameter:

- `--version=<version>` This parameter installs a certain WooCommerce Blocks version.
- `--gutenberg=<true>` This parameter installs and activates the latest version of the Gutenberg plugin.
- `--theme=<theme>` This parameter installs and activates the latest version of a certain theme.

### Example

The following example shows how to set up a testing enviuronment using the WooCommerce Blocks 7.3.0 plugin, the latest Gutenberg plugin and teh latest Storefront theme:

```
wp woo-test-environment setup --version=7.3.0 --gutenberg=true --theme=storefront
```

## Contributing

Contributions are always welcome! Feel free to create a new [issue](https://github.com/nielslange/woo-test-environment/issues) or [pull request](https://github.com/nielslange/woo-test-environment/pulls).

## Changelog

## 1.1.0 - July 6, 2022

- [Command fails at sidebar](https://github.com/nielslange/woo-test-environment/issues/2) (props @albarin)
- [Cart/Checkout inserted even if WooCommerce Blocks isn't installed](https://github.com/nielslange/woo-test-environment/issues/3) (props @albarin)

### 1.0.0 - March 31, 2022

- Initial commit
