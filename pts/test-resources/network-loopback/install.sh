#!/bin/sh

echo "#!/bin/sh

nc -d -l 9999 > /dev/null &
dd if=/dev/zero bs=1M count=10000 | nc localhost 9999
echo \$? > ~/test-exit-status" > network-loopback
chmod +x network-loopback
