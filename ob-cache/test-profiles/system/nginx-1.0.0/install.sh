#!/bin/bash
if which nginx>/dev/null 2>&1 ;
then
        echo 0 > ~/install-exit-status
else
        echo "ERROR: nginx is not found on the system! The Nginx web server (nginx) must be present within your system PATH."
        echo 2 > ~/install-exit-status
fi

mkdir $HOME/nginx_
mkdir $HOME/nginx_/logs
mkdir $HOME/nginx_/html
tar -xf http-test-files-1.tar.xz
mv -f http-test-files/* nginx_/html/
echo "
    error_log $HOME/nginx_/error.log;
    pid       $HOME/nginx_/nginx.pid;
    worker_processes  $NUM_CPU_CORES;    
    events {
      # No special events for this simple setup
    }
    http {
      server {
        listen       8089;
        server_name  localhost;
        # Set a number of log, temp and cache file options that will otherwise
        # default to restricted locations accessible only to root.
        error_log $HOME/nginx_/logs/nginx_host.error.log;
        access_log $HOME/nginx_/logs/nginx_host.access.log;
        client_body_temp_path $HOME/nginx_/client_body;
        fastcgi_temp_path $HOME/nginx_/fastcgi_temp;
        proxy_temp_path $HOME/nginx_/proxy_temp;
        scgi_temp_path $HOME/nginx_/scgi_temp;
        uwsgi_temp_path $HOME/nginx_/uwsgi_temp;
        # Serve local files
        location / {
          root $HOME/nginx_/html;
          index  index.html index.htm;
          try_files \$uri \$uri/ /index.html;
        }
      }
    }
" > nginx.conf

echo "diff --git a/Makefile b/Makefile
index 395b98a..01fdef6 100644
--- a/Makefile
+++ b/Makefile
@@ -75,7 +75,7 @@ \$(ODIR)/%.o : %.c
 LUAJIT  := \$(notdir \$(patsubst %.zip,%,\$(wildcard deps/LuaJIT*.zip)))
 OPENSSL := \$(notdir \$(patsubst %.tar.gz,%,\$(wildcard deps/openssl*.tar.gz)))
-OPENSSL_OPTS = no-shared no-psk no-srp no-dtls no-idea --prefix=\$(abspath \$(ODIR))
+OPENSSL_OPTS = no-asm --strict-warnings -D_DEFAULT_SOURCE no-shared no-psk no-srp no-dtls no-idea --prefix=\$(abspath \$(ODIR))
 \$(ODIR)/\$(LUAJIT): deps/\$(LUAJIT).zip | \$(ODIR)
        echo \$(LUAJIT)
" > fix_build_fail.patch

rm -rf wrk
tar -xf wrk-20210207.tar.xz
cd wrk
patch -p1 < ../fix_build_fail.patch
make -j $NUM_CPU_CORES
cd $HOME

echo "#!/bin/bash
key=\"\$1\"
CORE_NUMS=\$[\$NUM_CPU_CORES - 2]
if [[ \"\$key\" == \"long\" ]]; then
    ./wrk/wrk -t \$CORE_NUMS -c \$2 -d 60 http://127.0.0.1:8089/test.html  > \$LOG_FILE 2>&1
else
    ./wrk/wrk -H \"Connection: Close\" -t \$CORE_NUMS -c \$2 -d 60 http://127.0.0.1:8089/test.html > \$LOG_FILE 2>&1
fi
echo \$? > ~/test-exit-status

nginx -v > ~/pts-footnote 2>&1" > nginx

chmod +x nginx
