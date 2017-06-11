# Server Availability Monitor
SAM is intented to monitor all of your services to be sure they are working. It's a command line tool that checks servers status all the time.

# What server types does it support?

1. Http
2. MySQL
3. PostgreSql
4. Memcache
5. Redis

# How to add server for monitoring?

Run
```sh
bin/monitor manage:add
```

and follow add wizard commands.

# How to update server parameter?

Run
```sh
bin/monitor manage:edit http1
```

and follow update wizard commands. Or specify all data as arguments
```sh
bin/monitor manage:edit http1 resultCode 302
```

# How to delete server?

Run
```sh
bin/monitor manage:delete http1
```

# How to start monitoring?

Run
```sh
bin/monitor monitor
```

It will print any data only if servers are down or not responding.

# How to see all servers that are monitored?

Run
```sh
bin/monitor manage
```
