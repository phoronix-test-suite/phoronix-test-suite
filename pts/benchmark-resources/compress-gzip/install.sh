#!/bin/sh

cd $1

if [ ! -f ../pts-shared/pts-wav-sample-file.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/pts-wav-sample-file.tar.bz2 -O ../pts-shared/pts-wav-sample-file.tar.bz2
fi

rm -f bigfile
tar -jxvf ../pts-shared/pts-wav-sample-file.tar.bz2

for i in 1 2 3 4 5 6 7 8
do
cat pts-wav-sample-file.wav >> bigfile-orig
done

echo "#!/bin/sh
cat bigfile-orig > bigfile
/usr/bin/time -f \"Gzip Compress Time: %e Seconds\" gzip bigfile 2>&1
rm -f bigfile.gz" > compress-gzip
chmod +x compress-gzip

