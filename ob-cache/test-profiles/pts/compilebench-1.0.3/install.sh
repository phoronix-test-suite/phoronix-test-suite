#!/bin/sh

tar -jxvf compilebench-0.6.tar.bz2

echo "#!/bin/sh
cd compilebench-0.6/
rm -rf t/
mkdir t/
if which python2 &> /dev/null; then
   python2 ./compilebench \$@ > \$LOG_FILE 2>&1
elif which python2.7 &> /dev/null; then
   python2.7 ./compilebench \$@ > \$LOG_FILE 2>&1
fi

echo \$? > ~/test-exit-status" > compilebench
chmod +x compilebench
