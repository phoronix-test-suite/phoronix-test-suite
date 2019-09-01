#!/bin/sh

chmod +x cp2k-6.1-Linux-x86_64.ssmp
tar -xjf cp2k-6.1.tar.bz2

echo "#!/bin/sh
./cp2k-6.1-Linux-x86_64.ssmp -i cp2k-6.1/tests/Fist/benchmark/fayalite.inp > \$LOG_FILE 2>&1" > cp2k
chmod +x cp2k
