#!/bin/sh

echo "#!/bin/sh
\$TIMER_START
tar -xjf linux-2.6.32.tar.bz2
\$TIMER_STOP" > unpack-linux
chmod +x unpack-linux


