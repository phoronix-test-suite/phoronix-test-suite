#!/bin/sh

tar -xvf trislam.tar.gz
cp -f trislam-1.patch trislam/
cd trislam/
patch -p0 < trislam-1.patch
cd ..

echo "#!/bin/sh
cd trislam/
/usr/bin/time -f \"Triangle Slammer Run-Time: %e Seconds\" ./trislam 2>&1" > trislam-run
chmod +x trislam-run
