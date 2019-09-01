#!/bin/sh

tar -xzf stress-ng-0.07.26.tar.gz

cd ~/stress-ng-0.07.26
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
cd ~/stress-ng-0.07.26
./stress-ng \$@ > \$LOG_FILE 2>&1 
EOF
chmod +x stress-ng
