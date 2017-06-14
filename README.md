# Server Availability Monitor
SAM is intented to monitor all of your services to be sure that they are working. It's a command line tool that checks servers all the time.

[![Composer package](http://xn--e1adiijbgl.xn--p1acf/badge/wapmorgan/server-availability-monitor)](https://packagist.org/packages/wapmorgan/server-availability-monitor)

1. [Before usage](#before-usage)
2. [Supported services](#what-server-types-does-it-support)
3. [Typical workflow](#typical-workflow)
4. [Servers managing](#servers-managing)
5. [Reporters](#reporters)
6. [Server check details](#server-check-details)

# Before usage

## Installation a phar
The simpliet way to install SAM is just download a phar from [releases page](https://github.com/wapmorgan/ServerAvailabilityMonitor/releases), make it executable and put in one of binary folders:

```sh
chmod +x sam.phar
sudo mv sam.phar /usr/local/bin/sam
```

In this case you will should use `sam` command instead of `monitor` in all examples.

## Installation via composer
The preferred way to install SAM is via composer:

* global installation ([additional instructions](https://getcomposer.org/doc/03-cli.md#global)):

  ```sh
  composer global require wapmorgan/server-availability-monitor
  ```

* local installation:

  ```sh
  composer require wapmorgan/server-availability-monitor
  ```

Further I will use commands for SAM installed globally, but if you've installed it locally, just replace `monitor` command to `vendor/bin/monitor`.

## Full help

All sub-commands is described in help:

```sh
monitor list
```

# What server types does it support?

1. [Http](#http)
2. [MySQL](#mysql)
3. [PostgreSql](#postgresql)
4. [Memcache](#memcache)
5. [Redis](#redis)
6. [Gearman](#gearman)
7. [RabbitMQ](#rabbitmq)

# Typical workflow

1. Add servers for monitoring. It will ask for some additional information like type of server, hostname, port and other depending on type.
  ```sh
  $ monitor manage:add
  Please select your type of server (defaults to http)
    [0] http
    [1] mysql
    [2] postgresql
    [3] memcache
    [4] redis
    [5] gearman
    [6] rabbitmq
   > 1
  Provide IP-address or hostname of server to monitor: 127.0.0.1
  Provide port of server: 3306
  Username to access DB: root
  Password for username to access DB: root
  Please select name of server (default to mysql1):
  Successfully added to servers list
  ```

2. Configure check period
  ```sh
  $ monitor report:config checkPeriod
  Current value: 10
  Please provide new value: 7
  Successfully updated
  ```
  and email for failure reports
  ```sh
  $ monitor report:config email
  Select transport system for email:
    [0] disable
    [1] sendmail
    [2] SMTP
   > 1
  Provide From field: abc@gmail.com
  Provide To field: admin@gmail.com
  Testing sending
  ```

3. Run monitor
  ```sh
  $ monitor monitor
  ```

If any server is down, it will print an error and send a report to configured email:
```
$ monitor monitor
Check at Mon, 12 Jun 2017 02:29:25 +0300: 1 error
compaster.pro reported error
```

If you want more information, launch monitor with verbosity option:
```sh
$ monitor monitor -v
Check at Mon, 12 Jun 2017 02:29:27 +0300: 1 error
compaster.pro reported error: Http server reports 301 code when expecting 302
```

# Servers managing
- Updating server configuration
  ```sh
  $ monitor manage:edit http1
  ```
  or with one call
  ```sh
  $ monitor manage:edit http1 resultCode 302
  ```

- Deleting server from monitoring list
  ```sh
  $ monitor manage:delete http1
  ```

- Print all servers configured for monitoring
  ```sh
  $ monitor manage
  ```

# Reporters

## EmailReporter
EmailReporter sends you an email when one of services fails.

It is configurable by

```sh
$ monitor report:config email
```

## NotifyReporter
NotifyReporter reports a problem with a notification on your desktop via `notify-send` command when it's available in your system.

# Server check details

**For all servers hostname/ip and port is required parameters.**

## Http
For http server it can check result code of result. Typically it should be 200 (for most cases) or 302 / 301 (for redirecting pages).

## MySQL
For mysql server you should provide username/password for any user of DB. It will try to connect to DB.

## PostgreSQL
For pgsql server you should provide username/password for any user of DB. Also you can specify different name for a database from a username name. It will try to connect to this DB.

## Memcache
For memcache server there are not additional parameters.

## Redis
For redis server there are not additional parameters.

## Gearman
For gearman server there are not additional parameters.

## RabbitMQ
For rabbitmq server you should provide username/password for any user of service.
