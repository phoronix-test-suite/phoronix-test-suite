#!/bin/sh

unzip -o ctx_clock-1.zip
cc $CFLAGS ctx_clock.c -o ctx_clock
echo $? > ~/install-exit-status

echo "#!/bin/sh
./ctx_clock > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ctx-clock
chmod +x ctx-clock
