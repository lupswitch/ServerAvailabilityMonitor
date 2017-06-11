# Server Availability Monitor
SAM is intented to monitor all of your services to be sure they are working. It's a command line tool that checks servers status all the time.

# What server types does it support?

1. Http
2. MySQL
3. PostgreSql
4. Memcache
5. Redis

# Typical workflow

1. Add servers for monitoring
  ```sh
  bin/monitor manage:add
  ```

2. Configure check period
  ```sh
  bin/monitor report:config checkPeriod
  ```
  and email for failure reports
  ```sh
  bin/monitor report:config email
  ```
  
3. Run monitor
  ```sh
  bin/monitor monitor
  ```
  
If any server is down, it will print an error and send a report to configured email:
```
Check at Mon, 12 Jun 2017 02:29:25 +0300: 1 error
compaster.pro reported error
```

If you want more information, launch monitor with verbosity option:
```
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
