#!/bin/sh

unzip -o schbench-20180206.zip
cd schbench/
make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd schbench/
./schbench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > schbench-run
chmod +x schbench-run
