#!/bin/sh

echo "#!/bin/sh

nc -d -l 9999 > /dev/null &
\$TIMER_START
dd if=/dev/zero bs=1M count=10000 | nc localhost 9999
echo \$? > ~/test-exit-status
\$TIMER_STOP" > network-loopback
chmod +x network-loopback
