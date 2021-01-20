# Basic Object Normalization Gateway

Interfaces with multiple Cannabis APIs.
Normalizes to the OpenTHC API models.

## Install

1. Clone this Repository to somewhere clever `git clone $REPO /opt/openthc/bong`
1. Update the Apache Config (use `etc/apache2.conf` as a template)
1. Create the Base Database from `etc/schema.sql` and add the triggers.
1. Configure in `etc/database`


## Connect

You can use BONG through it's normal web-interface to view objects in real time or view logs.

BONG also provides and API -- so that other services can consume the data from the different back-ends easier.


```php
curl --cookie=cookie-file.dat https://$BONG/auth/open
```


## Sync & Cache

The APIs that BONG interfaces with don't all agree on how to do pages of data, or sorting, or filtering.
BONG has tools that work in the background to pull and cache data.

```php
curl https://$BONG/status
```


## Reading Objects

A low level, GET and POST/PUT interface exists.


```php
curl https://$BONG/license
```
