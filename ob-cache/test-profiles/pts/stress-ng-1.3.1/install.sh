#!/bin/sh

tar -xf stress-ng-0.11.07.tar.gz

cd stress-ng-0.11.07/
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
cd stress-ng-0.11.07/
./stress-ng \$@ > \$LOG_FILE 2>&1 
EOF
chmod +x stress-ng
