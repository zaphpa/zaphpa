## 1. Install PHPUnit

```bash
$ cd /path/to/zaphpa
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
$ php composer.phar test
```

If you hate typing "php composer.phar" and have root access:

```bash
$ sudo mv composer.phar /usr/local/bin/composer
$ composer -v
```

and then you can just use "composer" as a command.

## 2. Set up a testing URL

First, we'll set up a new custom domain so as not to conflict with any pre-existing servers.

```shell
$ sudo sh -c "echo '\n127.0.0.1  zaphpa.vm' >> /etc/hosts"
```

Now, if you're using PHP 5.4 or higher, you can simply run the built-in webserver like so and skip to (3): 

```
php -S zaphpa.vm:8080 -t /path/to/zaphpa/tests
```
More information is available on [php.net](http://php.net/manual/en/features.commandline.webserver.php).

Otherwise, if you prefer using Apache, Nginx, etc. (pick your poison), you'll need to set up a virtualhost 
so that it points to the Zaphpa test router in `/path/to/zaphpa/tests/index.php` and can process requests to 
`http://127.0.0.1:5454`. If you prefer to use a different URL, simply modify the value of `server_url` in `/path/to/zaphpa/tests/phpunit.xml`.


## 3. Run the tests
```
$ cd /path/to/zaphpa
$ composer test
```
