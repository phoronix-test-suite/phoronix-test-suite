#!/bin/sh

tar -xvf bzip2-1.0.5.tar.gz
tar -xvf pbzip2-1.0.2.tar.gz
cd bzip2-1.0.5/
make
cp -f libbz2.a ../pbzip2-1.0.2
cp -f bzlib.h ../pbzip2-1.0.2
cd ..
cd pbzip2-1.0.2/
make pbzip2-static

cd ..

cat > compress-pbzip2 <<EOT
#!/bin/sh
cd pbzip2-1.0.2/
\$TIMER_START
./pbzip2 -c -p\$NUM_CPU_CORES -r -5 ../compressfile > /dev/null 2>&1
\$TIMER_STOP
EOT
chmod +x compress-pbzip2
