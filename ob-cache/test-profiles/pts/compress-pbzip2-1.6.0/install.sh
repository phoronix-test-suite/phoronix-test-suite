#!/bin/sh

tar -zxvf bzip2-1.0.8.tar.gz
tar -zxvf pbzip2-1.1.13.tar.gz
cd bzip2-1.0.8/
make
cp -f libbz2.a ../pbzip2-1.1.13
cp -f bzlib.h ../pbzip2-1.1.13
cd ..
cd pbzip2-1.1.13/
make pbzip2-static
echo $? > ~/install-exit-status

cd ~
cat > compress-pbzip2 <<EOT
#!/bin/sh
cd pbzip2-1.1.13/
./pbzip2 -c -p\$NUM_CPU_CORES -r -5 ../FreeBSD-13.0-RELEASE-amd64-memstick.img > /dev/null 2>&1
EOT
chmod +x compress-pbzip2
