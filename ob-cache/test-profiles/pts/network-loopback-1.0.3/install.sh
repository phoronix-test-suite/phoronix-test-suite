#!/bin/sh

echo "#!/bin/sh

nc -d -l 9999 > /dev/null &
sleep 3
dd if=/dev/zero bs=1M count=10000 | nc -w 3 localhost 9999
echo \$? > ~/test-exit-status" > network-loopback
chmod +x network-loopback
