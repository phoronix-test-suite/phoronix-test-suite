#!/bin/sh

cd $1

if [ ! -f ../pts-shared/pts-trondheim.wav ]
  then
     tar -xvf ../pts-shared/pts-trondheim-wav.tar.gz -C ../pts-shared/
fi

cat > gzip_bigfile <<EOT
#!/bin/sh
for i in 1 2 3 4 5 6 7 8; do cat ../pts-shared/pts-trondheim.wav; done|gzip -c >/dev/null
EOT
chmod +x gzip_bigfile

cat > compress-gzip <<EOT
#!/bin/sh
/usr/bin/time -f "Gzip Compress Time: %e Seconds" ./gzip_bigfile 2>&1
EOT
chmod +x compress-gzip 


