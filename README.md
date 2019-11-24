# EasyPHP configurator

This script is made to apply a specific configuration to all PHP binaries in an EasyPHP Devserver install. It is intended to make the initial configuration as well as adding new PHP binaries easier, without having to manually adjust the ini files each time.

EasyPHP Devserver: http://easyphp.org

What the script does:

- Download the latest `cacert.pem` file and place it into the devserver's folder
- Edit the php.ini of each PHP binary installed in the server
- Enables openssl, including the certificate configuration
- Enables additional extensions
- Sets max upload size to 200M (including max POST size)
- Sets max execution time to 90 seconds
- Sets max memory to 600M

These are my personal developer preferences, but they can be changed in the `Configurator` class.

## Installation

1. Clone the project somewhere in your Webroot
1. Run `composer update` to install dependencies
1. Rename `config.dist.php` to `config.php`
1. Set the correct path to your EasyPHP folder in `config.php`
1. Run the `index.php` either in your browser or via command line

After this, run the script whenever you add new PHP binaries.
