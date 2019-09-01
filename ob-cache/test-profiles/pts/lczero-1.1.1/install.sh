#!/bin/sh

rm -rf  lc0-0.20.1
unzip -o lc0-0.20.1.zip
cd lc0-0.20.1
./build.sh
echo $? > ~/install-exit-status

cd ~
cp -f 0cf3fafcbd18e17d11d75d669d8dbf38eb89a57fbf0202196834433629da65ae lc0-0.20.1/build/release/weights

echo "#!/bin/sh
cd  lc0-0.20.1/build/release/
./lc0 \$@ --threads=\$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > lczero
chmod +x lczero
