#!/bin/sh
cd $1
g++ sample-pi-program.cpp -o sample-pi-program

echo "#!/bin/sh

time -f \"Pi Calculation Time: %e Seconds\" ./sample-pi-program 2>&1 | grep Seconds" > sample-program
chmod +x sample-program

