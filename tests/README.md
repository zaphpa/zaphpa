# Make sure you have the latest PEAR PHPUnit installed:
  * sudo pear channel-discover pear.phpunit.de
  * sudo pear channel-discover pear.symfony-project.com
  * sudo pear channel-discover components.ez.no
  * sudo pear update-channels
  * sudo pear upgrade-all
  * sudo pear install --alldeps phpunit/PHPUnit
  
# Run all tests with:
  * cd tests
  * phpunit . 
