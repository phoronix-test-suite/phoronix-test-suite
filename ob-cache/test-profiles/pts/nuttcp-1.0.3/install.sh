#!/bin/sh
tar -xf nuttcp-8.1.4.tar.bz2
cd nuttcp-8.1.4

if [ $OS_TYPE = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd nuttcp-8.1.4
./nuttcp-8.1.4 \$@ | sed 's/=/ /g' > \$LOG_FILE 2>1
echo \$? > ~/test-exit-status" > nuttcp
chmod +x nuttcp
