#!/bin/sh

g++ sample-pi-program.cpp -o sample-pi-program

echo "#!/bin/sh
./sample-pi-program 2>&1" > sample-program
chmod +x sample-program

