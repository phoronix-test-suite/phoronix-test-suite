#!/bin/sh

tar -jxvf compilebench-0.6.tar.bz2

echo "#!/bin/sh
cd compilebench-0.6/
rm -rf t/
mkdir t/
python2 ./compilebench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > compilebench
chmod +x compilebench
