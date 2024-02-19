#!/bin/sh
tar -xf stress-ng-0.16.04.tar.gz
cd stress-ng-0.16.04
if [ "$OS_TYPE" = "BSD" ]
then
	gmake
else
	make -j $NUM_CPU_PHYSICAL_CORES
fi
echo $? > ~/install-exit-status
cd ~
cat << EOF > stress-ng
#!/bin/sh
cd stress-ng-0.16.04
./stress-ng \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOF
chmod +x stress-ng
