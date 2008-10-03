#!/bin/sh

g++ sample-pi-program.cpp -o sample-pi-program

echo "#!/bin/sh
\$TIMER_START
./sample-pi-program 2>&1
\$TIMER_STOP" > sample-program
chmod +x sample-program

