#!/bin/sh

tar -xf stress-ng-0.13.02.tar.gz

cd stress-ng-0.13.02/
if [ "$OS_TYPE" = "BSD" ]
then
	gmake
else
	make
fi
echo $? > ~/install-exit-status

cd ~
cat << EOF > stress-ng
#!/bin/sh
cd stress-ng-0.13.02/
./stress-ng \$@ > \$LOG_FILE 2>&1 
EOF
chmod +x stress-ng
