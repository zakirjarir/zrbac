<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RBAC Table Names
    |--------------------------------------------------------------------------
    |
    | If you want to use different table names, you can change them here.
    |
    */
    'tables' => [
        'modules' => 'modules',
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_permission' => 'role_permission',
        'role_module' => 'role_module',
        'user_role' => 'user_role',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | Define default roles that should be created during installation or setup.
    |
    */
    'default_roles' => [
        'super-admin',
        'admin',
        'editor',
        'user',
    ],
];
