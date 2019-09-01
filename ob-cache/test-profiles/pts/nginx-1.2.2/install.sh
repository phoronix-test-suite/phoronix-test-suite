#!/bin/sh

mkdir $HOME/nginx_


tar -zxvf apache-ab-test-files-1.tar.gz
tar -zxvf nginx-1.9.9.tar.gz
tar -zxvf httpd-2.2.17.tar.gz

# we need apache for ab, compile only apr,apt-utils,pcre and ab
cd httpd-2.2.17/
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
cp -av httpd-2.2.17/support/ab nginx_/

cd nginx-1.9.9/
echo "
--- src/os/unix/ngx_user.c.orig
+++ src/os/unix/ngx_user.c
@@ -31,10 +31,6 @@ ngx_libc_crypt(ngx_pool_t *pool, u_char *key, u_char *salt, u_char **encrypted)
     struct crypt_data   cd;

     cd.initialized = 0;
-#ifdef __GLIBC__
-    /* work around the glibc bug */
-    cd.current_salt[0] = ~salt[0];
-#endif

     value = crypt_r((char *) key, (char *) salt, &cd);
" > REMOVE-WORKAROUND.patch

patch -p0 < REMOVE-WORKAROUND.patch

CFLAGS="-Wno-error -O3 -march=native $CFLAGS" CXXFLAGS="-Wno-error -O3 -march=native $CFLAGS" ./configure --prefix=$HOME/nginx_ --without-http_rewrite_module --without-http-cache 
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf nginx-1.9.9/
rm -rf httpd-2.2.17/

# patch listen port 80 -> 8088
echo "
--- nginx_/conf/nginx.conf.orig  2010-11-09 18:22:34.000000000 +0200
+++ nginx_/conf/nginx.conf       2010-11-09 18:17:14.000000000 +0200
@@ -33,7 +33,7 @@
     #gzip  on;

     server {
+        listen       8089;
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
