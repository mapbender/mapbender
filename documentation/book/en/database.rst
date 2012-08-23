Configuring the database
########################

The standard database definition in the config.yml looks like this:

.. code-block:: yaml

    doctrine:
        dbal:
            driver:   %database_driver%
            host:     %database_host%
            port:     %database_port%
            dbname:   %database_name%
            path:     %database_path%
            user:     %database_user%
            password: %database_password%
            charset:  UTF8
        orm:
            auto_generate_proxy_classes: %kernel_debug%

All values encapsulated in % are parameters, loaded from the parameters.yml. Therefore, to change the database,
modify the parameters values in the parameters.yml.
Possible values for the %database_driver% parameter are:

* pdo_sqlite - SQLite PDO driver
* pdo_mysql - MySQL PDO driver
* pdo_pgsql - PostgreSQL PDO driver
* oci8 - Oracle OCI8 driver
* pdo_oci - Oracle PDO driver
TODO: See other drivers in the Doctrine docs

The parameters should be self-explanatory except for the %database_path% - that's the file path a SQLite database
would be stored. If you don't use a SQLite database, don't delete the parameter from the parameters.yml though.
It would throw an error and providing some nonsense value here doesn't hurt the other drivers.

Using multiple databases
~~~~~~~~~~~~~~~~~~~~~~~~

Using multiple databases is easy with Mapbender3 and advised if you want to seperate your own data from Mapbender's.
This is usefull in a scenario where you have your own custom code provided by an non-Mapbender bundle.

There's always a default database connection and all Mapbender code assumes that it can access it's data using that
default database connection.

So if your code wants to use a different database you have to define a second named database connection and always
use that named database connection. Other than that, there's nothing to do.

Here is an example for an database connection block in the config.yml with two connections:

.. code-block:: yaml

    doctrine:
        dbal:
            default: mapbender
            mapbender:
                driver:   %database_driver%
                host:     %database_host%
                port:     %database_port%
                dbname:   %database_name%
                path:     %database_path%
                user:     %database_user%
                password: %database_password%
                charset:  UTF8
            custom:
                driver:   %database2_driver%
                host:     %database2_host%
                port:     %database2_port%
                dbname:   %database2_name%
                path:     %database2_path%
                user:     %database2_user%
                password: %database2_password%
                charset:  UTF8
        orm:
            auto_generate_proxy_classes: %kernel_debug%

