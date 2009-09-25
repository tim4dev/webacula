#!/bin/sh
#############################################################
# Usage:
#
# ./runtest.sh --exclude-group nonreusable,restore,logbook
# ./runtest.sh --group test-test
# ./runtest.sh --filter testJobFindByVolumeName
#
#############################################################

cd prepare_tests
sudo ./clean_all.sh
sudo ./prepare.sh
cd ..

phpunit $* --configuration phpunit_report.xml --colors --stop-on-failure AllTests.php

