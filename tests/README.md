## 1. [Install PHPUnit](https://github.com/sebastianbergmann/phpunit#installation)

## 2. Set up a testing URL

If you're using PHP 5.4 or higher, you can simply run the built-in webserver like so and skip to (3): 
```
php -S localhost:8000 -t /path/to/zaphpa/tests
```
More information is available on [php.net](http://php.net/manual/en/features.commandline.webserver.php).

In Apache, Nginx, etc. (pick your posion) set up a virtualhost so that it points to the
Zaphpa test router under `tests/index.php` and can process requests to 
`http://localhost:8000` (or modify the value of `server_url` in the `phpunit.xml` file).

For instance, for Nginx:
```
server {
  listen       8000;
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