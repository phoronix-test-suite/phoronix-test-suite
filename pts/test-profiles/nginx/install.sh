#!/bin/sh

mkdir $HOME/nginx_


tar -zxvf apache-ab-test-files-1.tar.gz
tar -zxvf nginx-0.8.53.tar.gz
tar -zxvf httpd-2.2.11.tar.gz

# we need apache for ab, compile only apr,apt-utils,pcre and ab
cd httpd-2.2.11/
./configure --prefix=$HOME/httpd_ --enable-static-ab --without-http-cache
cd srclib/apr
make
cd ../apr-util
make
cd ../pcre
make
cd ../../support
make ab
cd ../..
cp -av httpd-2.2.11/support/ab nginx_/

cd nginx-0.8.53/
./configure --prefix=$HOME/nginx_ --without-http_rewrite_module --without-http-cache
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf nginx-0.8.53/
rm -rf httpd-2.2.11/

# patch listen port 80 -> 8088
echo "
--- nginx_/conf/nginx.conf.orig  2010-11-09 18:22:34.000000000 +0200
+++ nginx_/conf/nginx.conf       2010-11-09 18:17:14.000000000 +0200
@@ -33,7 +33,7 @@
     #gzip  on;

     server {
+        listen       8088;
-        listen       80;
         server_name  localhost;

         #charset koi8-r;
" > CHANGE-NGINX-PORT.patch

patch -p0 < CHANGE-NGINX-PORT.patch
mv -f test.html nginx_/html/
mv -f pts.png nginx_/html/

echo "#!/bin/sh
./nginx_/ab \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > nginx

chmod +x nginx
