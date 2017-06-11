# Server Availability Monitor
SAM is intented to monitor all of your services to be sure they are working. It's a command line tool that checks servers status all the time.

# What server types does it support?

1. [Http](#http)
2. [MySQL](#mysql)
3. [PostgreSql](#postgresql)
4. [Memcache](#memcache)
5. [Redis](#redis)

# Typical workflow

1. Add servers for monitoring. It will ask for some additional information like type of server, hostname, port and other depending on type.
  ```sh
  $ bin/monitor manage:add
  Please select your type of server (defaults to http)
    [0] http
    [1] mysql
    [2] postgresql
    [3] memcache
    [4] redis
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
  $ bin/monitor report:config checkPeriod
  Current value: 10
  Please provide new value: 7
  Successfully updated
  ```
  and email for failure reports
  ```sh
  $ bin/monitor report:config email
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
  $ bin/monitor monitor
  ```
  
If any server is down, it will print an error and send a report to configured email:
```
$ bin/monitor monitor
Check at Mon, 12 Jun 2017 02:29:25 +0300: 1 error
compaster.pro reported error
```

If you want more information, launch monitor with verbosity option:
```sh
$ bin/monitor monitor -v
Check at Mon, 12 Jun 2017 02:29:27 +0300: 1 error
compaster.pro reported error: Http server reports 301 code when expecting 302
```

# Servers managing
- Updating server configuration
  ```sh
  bin/monitor manage:edit http1
  ```
  or with one call
  ```sh
  bin/monitor manage:edit http1 resultCode 302
  ```

- Deleting server from monitoring list
  ```sh
  bin/monitor manage:delete http1
  ```

- Print all servers configured for monitoring
  ```sh
  bin/monitor manage
  ```

# Server check details

**For all servers hostname/ip and port is required parameters.**

## Http
For http server it can check result code of result. Typically it should be 200 (for most cases) or 302 / 301 (for redirecting pages).

## MySQL
For mysql server you should provide username/password for any user of DB. It will try to connect to DB.

## PostgreSQL
For pgsql server you should provide username/password for any user of DB. It will try to connect to DB.

## Memcache
For memcache server there are not additional parameters.

## Redis
For redis server there are not additional parameters.
