#!/bin/sh
tar -xf helsing-1.0-beta.tar.gz

cd helsing-1.0-beta/helsing/
sed "s|^#define THREADS .*|#define THREADS $NUM_CPU_CORES|g" configuration.h > tmp
mv tmp configuration.h
make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./helsing-1.0-beta/helsing/helsing \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/helsing
chmod +x ~/helsing

