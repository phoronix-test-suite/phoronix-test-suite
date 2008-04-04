#!/bin/sh

cd $1

if [ ! -f ../pts-shared/pts-wav-sample-file.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
fi

rm -f bigfile
tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2

rm -f bigfile-orig
for i in 1 2 3 4 5 6 7 8
do
cat pts-wav-sample-file.wav >> bigfile-orig
done

cat > gzip_bigfile <<EOT
#!/bin/sh
gzip -c bigfile-orig >/dev/null
EOT
chmod +x gzip_bigfile

cat > compress-gzip <<EOT
#!/bin/sh
/usr/bin/time -f "Gzip Compress Time: %e Seconds" ./gzip_bigfile 2>&1
EOT
chmod +x compress-gzip 


