ypf
===

a micro php5 framework
````
swoole
````
````
nginx

    server {
        listen       80;
        server_name  vhost1.dev;

        #charset koi8-r;

        access_log  logs/vhost1.access.log  main;

        location / {
            root   E:\Github\Ypf\demo\public_html;
            index  index.html index.htm index.php;
	    try_files $uri @router;
        }

	location @router {
		rewrite ^/(.+)$ /index.php?_route_=$1 last;
	}

        location ~ \.php$ {
            root           E:\Github\Ypf\demo\public_html;
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include        fastcgi_params;
        }

    }
````
