<?php

return array(
    'mongo' => array(
        'database' => '',
        'hostname'  => '',
        'port' => '',

        /* The following is only required if the above key is set to TRUE */
        'auth' => array(
            'requireAuthentication' => false,
            'username'  => '',
            'password'  => '',
        ),
    ),
    // MongoDB adapter access
    'service_manager' => array(
        'factories' => array(
            'Mongo\Db\Adapter\Adapter' => 'Mongo\Db\Adapter\AdapterServiceFactory',
        ),
    ),
);
