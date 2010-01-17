#!/bin/sh

tar -zxvf trislam.tar.gz
cp -f trislam-1.patch trislam/
cd trislam/
patch -p0 < trislam-1.patch
cd ..

echo "#!/bin/sh
cd trislam/
\$TIMER_START
./trislam 2>&1
\$TIMER_STOP" > trislam-run
chmod +x trislam-run
