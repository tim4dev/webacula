### Usage:
### ./runtest.sh --exclude-group nonreusable,restore,logbook
### ./runtest.sh --group test-test --configuration phpunit_report.xml 

#phpunit $* --colors --stop-on-failure AllTests.php
phpunit $* --configuration phpunit_report.xml --colors --stop-on-failure AllTests.php


