#!/bin/sh

tar -zxvf bzip2-1.0.6.tar.gz
tar -zxvf pbzip2-1.1.12.tar.gz
cd bzip2-1.0.6/
make
cp -f libbz2.a ../pbzip2-1.1.12
cp -f bzlib.h ../pbzip2-1.1.12
cd ..
cd pbzip2-1.1.12/
make pbzip2-static
echo $? > ~/install-exit-status

cd ~
gunzip linux-4.3.tar.gz

cat > compress-pbzip2 <<EOT
#!/bin/sh
cd pbzip2-1.1.12/
./pbzip2 -c -p\$NUM_CPU_CORES -r -5 ../linux-4.3.tar > /dev/null 2>&1
EOT
chmod +x compress-pbzip2
