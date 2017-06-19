# Server Availability Monitor
SAM is intented to monitor all of your services to be sure that they are working. It's a command line tool that checks servers all the time.

[![Composer package](http://xn--e1adiijbgl.xn--p1acf/badge/wapmorgan/server-availability-monitor)](https://packagist.org/packages/wapmorgan/server-availability-monitor)
[![Total Downloads](https://poser.pugx.org/wapmorgan/server-availability-monitor/downloads)](https://packagist.org/packages/wapmorgan/server-availability-monitor)
[![License](https://poser.pugx.org/wapmorgan/server-availability-monitor/license)](https://packagist.org/packages/wapmorgan/server-availability-monitor)

1. [Before usage](#before-usage)
2. [Supported services](#what-server-types-does-it-support)
3. [Typical workflow](#typical-workflow)
4. [Servers managing](#servers-managing)
5. [Reporters](#reporters)
6. [Logging](#logging)
7. [Server configuration details](#server-configuration-details)
8. [Advanced settings](#advanced-settings)

# Before usage

## Installation a phar
The simpliet way to install SAM is just download a phar from [releases page](https://github.com/wapmorgan/ServerAvailabilityMonitor/releases), make it executable and put it in one of folders listed in your $PATH:

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

Further I will use commands for SAM installed globally, but if you've installed it locally, just replace `monitor` command with `vendor/bin/monitor`.

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
  email for failure reports
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
  and enable logging
  ```sh
  $ monitor report:config log
  Enable or disable logging of check results (disabled now):
    [0] disable
    [1] enable
   > 1
  Successfully updated
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

# Logging
SAM supports logging of check results.

To configure this feature call
```sh
$ monitor report:config log
```

Logger stores information about availability every hour for every server. If any check during a hour fails, the whole hour will be marked as failed. Log file is very lite-weight! It needs only 5 bytes to store information about check results of 1 server per day.

To see log you can use `log` command. It supports full and short form.

**Full log form**

In full form it shows check results for given day, month, year and all time summary. By default it shows log for current date. You can change it with `--day=`, `--month=` and `--year=` options.


```sh
$ monitor log docker-cms
+------------+----------+
| All log    | 2017     |
+------------+----------+
| docker-cms | +        |
+------------+----------+
| All checks passed     |
+------------+----------+
+--------------+----------+
| Log for 2017 | 6        |
+--------------+----------+
| docker-cms   | +        |
+--------------+----------+
| All checks passed       |
+--------------+----------+
+-----------------+----------+
| Log for 2017-06 | 19       |
+-----------------+----------+
| docker-cms      | +        |
+-----------------+----------+
| All checks passed          |
+-----------------+----------+
+--------------------+----+----+----+----+----+----+----+---+---+---+----+
| Log for 2017-06-19 | 0  | 1  | 2  | 3  | 4  | 5  | 6  | 7 | 8 | 9 | 10 |
+--------------------+----+----+----+----+----+----+----+---+---+---+----+
| docker-cms         | +  | +  | +  | +  | +  | +  | +  | + | + | + | +  |
+--------------------+----+----+----+----+----+----+----+---+---+---+----+
| All checks passed                                                      |
+--------------------+----+----+----+----+----+----+----+---+---+---+----+
```

**Short log form**

In short form (additional `-s` or `--short` option) you can select what information you want to see:

- no options for day log
- `--all-days` option for month log
- `--all-months` option for year log
- `--all-years` option for all time log

By default it shows log for current date. You can change it with `--day=`, `--month=` and `--year=` options.


```sh
$ monitor log -s docker-cms
+-------------------+--------------------+
| docker-cms        | Log for 2017-06-19 |
+-------------------+--------------------+
| All checks passed | +++++++++++        |
+-------------------+--------------------+
```

# Server configuration details

**For all servers hostname/ip and port are required parameters.**

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

# Advanced settings

Also, you can change following settings:

- **checkTimeOut** - maximum time consumed to check one service. By default is 3 sec.
- **emailPeriod** - minimum time until SAM send you next email report after the first one.

```sh
$ monitor report:config SETTING
```
