# ISPManager recipe

## Installing
Add to your deploy.php
~~~php
require 'contrib/ispmanager.php';
~~~

## Configuration

Project owner login (Default www-root)
~~~php
set('ispmanager_owner', 'www-root');
~~~

Project owner document root
~~~php
set('ispmanager_doc_root', '/var/www/www-root/data/');
~~~
API connection DSN
~~~php
add ('ispmanager', [
    'api' => [
        'dsn' => 'https://login:password@host:port/path',
    ]
]);
~~~
API connection security (Default true)
Use session based auth instead send login/password at each request
~~~php
add ('ispmanager', [
    'api' => [
        'secure' => true,
    ]
]);
~~~
Virtual host default configuration (Other keys can be finded at recipe code).
{{domain}} - current domain macro replacement.
~~~php
add('vhost', [
    'email' => 'info@{{domain}}',
]);
~~~

---

## Tasks configuration
**Create new database**
* `dsn` - connection string (**See features**)
* `charset` - database charset
* `skipIfExist` - show warning message (`true`) or throw exception (`false`) if database exists
~~~php
add ('ispmanager', [
    'createDatabase' => [
        'dsn' => 'mysql://login:password@host:port/dbname',
        'charset' => 'utf8',
        'skipIfExist' => true,
    ],
]);
~~~
---
#### DSN features
* Use ``*`` for `login` for use database name as login
* Use ``*`` for `password` to generate it
* Use any another value for `login` for use exist username
---
**Delete database**
* `dsn` - connection string
* `skipIfNotExist` - show warning message (`true`) or throw exception (`false`) if database not exist
~~~php
add ('ispmanager', [
    'deleteDatabase' => [
        'dsn' => 'mysql://127.0.0.1:3310/testddb311',
        'skipIfNotExist' => true,
    ],
]);
~~~

**Create new domain**
* `name` - domain name
* `skipIfExist` - show warning message (`true`) or throw exception (`false`) if domain exists
~~~php
add ('ispmanager', [
    'createDomain' => [
        'name' => 'test-173.somedomain.com',
        'skipIfExist' => true,
    ],
]);
~~~

**Delete domain**
* `name` - domain name
* `removeDir` - delete domain folder from disk (Default `false`)
* `skipIfNotExist` - show warning message (`true`) or throw exception (`false`) if domain not exist
~~~php
add ('ispmanager', [
    'deleteDomain' => [
        'name' => 'test-173.somedomain.com',
        'removeDir' => false,
        'skipIfNotExist' => false,
    ],
]);
~~~

**Modify php settings for domain**
Call ispmanager:print-php-list for print list of available versions and modes
* `name` - domain name
* `mode` - php mode
* `version` - php version code
~~~php
add ('ispmanager', [
    'phpSelect' => [
        'name' => 'test.somedomain.com',
        'mode' => 'php_mode_fcgi_nginxfpm',
        'version' => 'isp-php71'
]]);
~~~

**Create new domain alias**
* `name` - domain name
* `alias` - alias string (Can be several aliases seperated by whitespace)
* `skipIfExist` - show warning message (`true`) or throw exception (`false`) if domain alias exists
~~~php
add ('ispmanager', [
    'createAlias' => [
        'name' => 'test.somedomain.com',
        'alias' => 'testwww.somedomain.com testwww2.somedomain.com',
        'skipIfExist' => true,
    ],
]);
~~~

**Delete domain alias**
* `name` - domain name
* `alias` - alias string (Can be several aliases seperated by whitespace)
* `skipIfNotExist` - show warning message (`true`) or throw exception (`false`) if domain alias not exist
~~~php
add ('ispmanager', [
    'deleteAlias' => [
        'name' => 'test.somedomain.com',
        'alias' => 'testwww2.somedomain.com',
        'skipIfNotExist' => false,
    ],
]);
~~~
---
## Tasks
You can just run auto processing or any recipe task
- `ispmanager:process` tasks auto processing by configuration
- `ispmanager:db-create` create new database
- `ispmanager:db-delete` delete database
- `ispmanager:domain-create` create new domain
- `ispmanager:domain-delete` delete domain
- `ispmanager:domain-php-select` select php settings for domain
- `ispmanager:domain-alias-create` create mew domain alias
- `ispmanager:domain-alias-delete` delete domain alias
- `ispmanager:print-php-list` print list of PHP modes and versions