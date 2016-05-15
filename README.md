# OdooClient

OdooClient is an Odoo client for PHP. It is inspired on [OpenERP API][1] from simbigo and uses a more or less similar API. Instead of an own XML-RPC client it depends on the XML-RPC and XML libraries from ZF.

## Supported versions

This library should work with Odoo 8 and 9. If you find any any incompatibilities, please create an issue or submit a pull request.

__Known issues__

- The `Odoo::getReport()` method in v0.2.2 and lower does not work with Odoo 9.

## Usage

Instantiate a new client.

```php
use Jsg\Odoo\Odoo;

$url = 'example.odoo.com/xmlrpc/2';
$database = 'example-database';
$user = 'user@email.com';
$password = 'yourpassword';

$client = new Odoo($url, $database, $user, $password);
```

For the client to work you have to include the `/xmlrpc/2` part of the url.

When you need to tweak the HTTP client used by the XML-RPC client, you can inject a custom HTTP client via the constructor or the `Odoo::setHttpClient` method.

```php
use Jsg\Odoo\Odoo;
use Zend\Http\Client as HttpClient;

$httpClient = new HttpClient(null, [
    'sslverifypeer' => false,
]);

// constructor argument
$client = new Odoo($url, $database, $user, $password, $httpClient);

// or setter
$client = new Odoo($url, $database, $user, $password);
$client->setHttpClient($httpClient);
```

### xmlrpc/2/common endpoint

Getting version information.

```php
$client->version();
```

Getting timezone information.

```php
$client->timezone();
```

There is no login/authenticate method. The client does authentication for you, that is why the credentials are passed as constructor arguments.

### xmlrpc/2/object endpoint

Search for records.

```php
$criteria = [
  ['customer', '=', true],
];
$limit = 10;
$offset = 0;

$client->search('res.partner', $criteria, $offset, $limit);
```

Reading records.

```php
$ids = $client->search('res.partner', [['customer', '=', true]], 0, 10);

$fields = ['name', 'email', 'customer'];

$customers = $client->read('res.partner', $ids, $fields);
```

Creating records.

```php
$data = [
  'name' => 'John Doe',
  'email' => 'foo@bar.com',
];

$id = $client->create('res.partner', $data);
```

Updating records.

```php
// change email address of user with current email address foo@bar.com
$ids = $client->search('res.partner', [['email', '=', 'foo@bar.com']], 0, 1);

$client->write('res.partner', $ids, ['email' => 'baz@quux.com']);

// 'uncustomer' the first 10 customers
$ids = $client->search('res.partner', [['customer', '=', true]], 0, 10);

$client->write('res.partner', $ids, ['customer' => false]);
```

Deleting records.

```php
$ids = $client->search('res.partner', [['email', '=', 'baz@quuz.com']], 0, 1);

$client->unlink('res.partner', $ids);
```

Get report in base64 format.

```php
$ids = $client->search('res.partner', [['customer', '=', true]], 0, 10);

$report = $client->getReport('res.partner', $ids);
```

[1]: https://bitbucket.org/simbigo/openerp-api

# License

MIT License. Copyright (c) 2014 Jacob Steringa.
