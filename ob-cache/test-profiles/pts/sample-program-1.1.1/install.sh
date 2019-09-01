#!/bin/sh

c++ sample-pi-program.cpp -o sample-pi-program
echo $? > ~/install-exit-status

echo "#!/bin/sh
# Run a few times as otherwise too quick on modern systems....
./sample-pi-program
./sample-pi-program
./sample-pi-program
./sample-pi-program
./sample-pi-program" > sample-program
chmod +x sample-program
