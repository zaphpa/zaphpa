## 1. [Install PHPUnit](https://github.com/sebastianbergmann/phpunit#installation)

```
$ sudo pear channel-discover pear.phpunit.de
$ sudo pear channel-discover pear.symfony-project.com
$ sudo pear channel-discover components.ez.no
$ sudo pear update-channels
$ sudo pear upgrade-all
$ sudo pear install --alldeps phpunit/PHPUnit
```

## 2. Set up a testing URL

If you're using PHP 5.4 or higher, you can simply run the built-in webserver like so and skip to (3): 
```
php -S localhost:8080 -t /path/to/zaphpa/tests
```
More information is available on [php.net](http://php.net/manual/en/features.commandline.webserver.php).

In Apache, Nginx, etc. (pick your posion) set up a virtualhost so that it points to the
Zaphpa test router in `/path/to/zaphpa/tests/index.php` and can process requests to 
`http://localhost:8080`. If you prefer to use a different URL, simply modify the value of `server_url` in `/path/to/zaphpa/tests/phpunit.xml`.

For instance, for Nginx:
```
server {
  listen       8080;
  server_name  localhost;
  root         /path/to/zaphpa/tests;

  location / {
    try_files  $uri $uri/ /index.php?q=$uri&$args;
    index      index.php;
  }
}
```

## 3. Run the tests
```
$ cd /path/to/zaphpa/tests
$ phpunit . 
```