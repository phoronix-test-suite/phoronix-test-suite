#!/bin/sh

tar -xjvf luxmark-linux64-v3.1.tar.bz2

echo "#!/bin/sh
cd luxmark-v3.1/
./luxmark \$@ > \$LOG_FILE 2> /dev/null
echo \$? > ~/test-exit-status" > luxmark
chmod +x luxmark
