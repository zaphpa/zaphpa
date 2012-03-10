# Make sure you have the latest PEAR PHPUnit installed:
  * sudo pear channel-discover pear.phpunit.de
  * sudo pear channel-discover pear.symfony-project.com
  * sudo pear channel-discover components.ez.no
  * sudo pear update-channels
  * sudo pear upgrade-all
  * sudo pear install --alldeps phpunit/PHPUnit
  
# Set up a proper virtualhost for testing 

In Apache, NginX, etc. (choose your posion) set up a virtualhost so that it points to 
Zaphpa controller under: tests/router.sample.php and can  process requests to 
http://zaphpa.vm:8080/ (or  modify the value of the base url in phpunit.xml file)

For instance, for Nginx:
<pre>
... snippet ...
server {
    listen   8080;
    server_name  zaphpa.vm;
    root /path/to/zaphpa/code/tests;
    
    index index.php router.sample.php;
    
location / {
      if (!-e $request_filename) {
        rewrite ^/(.*)$ /router.sample.php?q=$1 last;
      }
}
...
</pre>

# Run all tests with:
  * cd tests
  * phpunit . 
