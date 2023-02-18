#!/bin/sh

cc -Wall -O3 -pthread -o aio-stress-bin aio-stress.c -laio
echo $? > ~/install-exit-status

# add support for allowing aio-test-file to be on removable media devices
echo "#!/bin/sh
./aio-stress-bin \$@ aio-test-file > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -f aio-test-file" > aio-stress
chmod +x aio-stress
