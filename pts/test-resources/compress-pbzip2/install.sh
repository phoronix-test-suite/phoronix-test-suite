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

cat > pbzip2_process <<EOT
#!/bin/sh
cd pbzip2-1.0.2/
./pbzip2 -c -p\$NUM_CPU_CORES -r -5 ../compressfile > /dev/null
EOT
chmod +x pbzip2_process


cat > compress-pbzip2 <<EOT
#!/bin/sh
/usr/bin/time -f "PBZIP2 Compress Time: %e Seconds" ./pbzip2_process 2>&1
EOT
chmod +x compress-pbzip2
