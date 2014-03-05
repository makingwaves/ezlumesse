eZLumesse
=========

eZLumesse - development
=======================

In this extension we're using unit tests. After changing or adding new functionality
make sure that you've unit tested it as well. Current unit tests can be performed
by running this command:
``$ php tests/runtests.php --dsn mysql://root:pass@localhost/db_name --filter="SoapTest" --db-per-test``