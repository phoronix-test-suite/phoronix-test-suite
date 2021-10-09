#!/bin/sh

mkdir $HOME/nginx_

tar -xf http-test-files-1.tar.xz
tar -xf nginx-1.21.1.tar.gz

cd nginx-1.21.1/
CFLAGS="-Wno-error -O3 -march=native $CFLAGS" CXXFLAGS="-Wno-error -O3 -march=native $CFLAGS" ./configure --prefix=$HOME/nginx_ --without-http_rewrite_module --without-http-cache 
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf nginx-1.21.1/

sed -i "s/worker_processes  1;/worker_processes  auto;/g" nginx_/conf/nginx.conf
sed -i "s/        listen       80;/        listen       8089;/g" nginx_/conf/nginx.conf

rm -rf go
go get -u github.com/codesenberg/bombardier

mv -f http-test-files/* nginx_/html/

echo "#!/bin/sh
~/go/bin/bombardier \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > nginx

chmod +x nginx
