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

First, we'll set up a new custom domain so as not to conflict with any pre-existing servers.

```
$ sudo sh -c "echo '\n127.0.0.1  zaphpa.vm' >> /etc/hosts"
```

Now, if you're using PHP 5.4 or higher, you can simply run the built-in webserver like so and skip to (3): 
```
php -S zaphpa.vm:8080 -t /path/to/zaphpa/tests
```
More information is available on [php.net](http://php.net/manual/en/features.commandline.webserver.php).

Otherwise, if you prefer using Apache, Nginx, etc. (pick your poison), you'll need to set up a virtualhost 
so that it points to the Zaphpa test router in `/path/to/zaphpa/tests/index.php` and can process requests to 
`http://zaphpa.vm:8080`. If you prefer to use a different URL, simply modify the value of `server_url` in `/path/to/zaphpa/tests/phpunit.xml`.

For instance, for Nginx:
```
server {
  listen       8080;
  server_name  zaphpa.vm;
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