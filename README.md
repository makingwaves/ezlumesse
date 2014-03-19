eZLumesse 1.0
=============

Script
======
An example how to run the import script:
``php extension/sqliimport/bin/php/sqlidoimport.php -ssuperadmin_orkla_no --source-handlers=ezlumesse --options="ezlumesse::parent_node=81860"``

eZLumesse - development
=======================

In this extension we're using unit tests. After changing or adding new functionality
make sure that you've unit tested it as well. Current unit tests can be performed
by running this command:
``$ php tests/runtests.php --dsn mysql://root:pass@localhost/db_name --filter="SoapTest" --db-per-test``