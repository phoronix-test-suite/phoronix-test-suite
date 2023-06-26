#!/bin/sh
tar -xf mocassin-mocassin.2.02.73.3.tar.gz
cd mocassin-mocassin.2.02.73.3
IPREFIX=`pwd`
sed -i "s,PREFIX=/usr,PREFIX=$IPREFIX,g" Makefile 
mkdir -p share/mocassin/data
mkdir -p share/mocassin/dustData
cp -va data/* share/mocassin/data
cp -va dustData/* share/mocassin/dustData
make
echo $? > ~/install-exit-status
mkdir input 
mkdir output
cd ~/
cat>mocassin<<EOT
#!/bin/sh
cd mocassin-mocassin.2.02.73.3
rm -f input/*
cp benchmarks/\$1/* input/
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./mocassin > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x mocassin

