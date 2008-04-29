#!/bin/sh

cd $1

if [ ! -f ../pts-shared/pts-wav-sample-file.wav ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
     tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2 -C ../pts-shared/
     rm -f ../pts-shared/pts-wav-sample-file.tar.bz2
fi

cat > gzip_bigfile <<EOT
#!/bin/sh
for i in 1 2 3 4 5 6 7 8; do cat ../pts-shared/pts-wav-sample-file.wav; done|gzip -c >/dev/null
EOT
chmod +x gzip_bigfile

cat > compress-gzip <<EOT
#!/bin/sh
/usr/bin/time -f "Gzip Compress Time: %e Seconds" ./gzip_bigfile 2>&1
EOT
chmod +x compress-gzip 


