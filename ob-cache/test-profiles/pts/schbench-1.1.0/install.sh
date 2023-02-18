#!/bin/sh
tar -xf schbench-20210909.tar.xz
cd schbench-20210909
make
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd schbench-20210909
NR_WORKER=\$((NUM_CPU_CORES/4))
if [ \$NR_WORKER -eq 0 ]
then
	\$NR_WORKER=1
fi
./schbench \$@ -t \$NR_WORKER > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > schbench
chmod +x schbench

