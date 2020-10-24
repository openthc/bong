# Basic Object Normalization Gateway

Interfaces with multiple Cannabis APIs.
Normalizes to the OpenTHC API models.


## Connect

```php
$bong = new \OpenTHC\CRE\Bong($cfg);
$bong->auth($cfg_auth);
// do stuff
```


## Sync & Cache

The APIs that BONG interfaces with don't all agree on how to do pages of data, or sorting, or filtering.
BONG has tools that work in the background to pull and cache data.

```php
$stat = $bong->getStatus();
```


## Reading Objects

A low level, GET and POST/PUT interface exists.


```php
$res = $bong->get('/object?page=0&sort=created_at');
```


## High Level API

It's also possible to interface with the objects at a higher level.


```php
$obj = $bong->lot()->single($oid);
$obj_list = $bong->lot()->search($arg);
$res = $bong->lot()->update($obj);
```
