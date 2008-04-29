#!/bin/sh

cd $1

tar -xvf bzip2-1.0.5.tar.gz
tar -xvf pbzip2-1.0.2.tar.gz
cd bzip2-1.0.5/
make
cp -f libbz2.a ../pbzip2-1.0.2
cp -f bzlib.h ../pbzip2-1.0.2
cd ..
cd pbzip2-1.0.2/
make pbzip2-static

if [ ! -f ../pts-shared/pts-wav-sample-file.wav ]
  then
     tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2 -C ../pts-shared/
fi

cd ..
rm -f bigfile
for i in 1 2 3 4 5 6 7 8
do
	cat ../pts-shared/pts-wav-sample-file.wav >> bigfile
done

cat > compress-pbzip2 <<EOT
#!/bin/sh
cd pbzip2-1.0.2/
/usr/bin/time -f "PBZIP2 Compress Time: %e Seconds" ./pbzip2 -k -f -p\$NUM_CPU_CORES -r -5 ../bigfile 2>&1
rm -f bigfile.bz2
EOT
chmod +x compress-pbzip2
