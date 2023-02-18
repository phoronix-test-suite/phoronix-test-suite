#!/bin/sh
tar -xf stress-ng-0.14.06.tar.gz
cd stress-ng-0.14.06
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
cd stress-ng-0.14.06
./stress-ng \$@ > \$LOG_FILE 2>&1 
echo \$? > ~/test-exit-status
EOF
chmod +x stress-ng
