#!/bin/sh

tar -xvf stream-2009-04-11.tar.gz
cc stream.c -o stream-bin
echo \$? > ~/test-exit-status

echo "#!/bin/sh
./stream-bin > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > stream
chmod +x stream
