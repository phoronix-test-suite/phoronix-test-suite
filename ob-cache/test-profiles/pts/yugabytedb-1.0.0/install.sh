#!/bin/bash
rm -rf yugabyte-2.19.0.0
if [ $OS_ARCH = "aarch64" ]
then
	tar -xf yugabyte-2.19.0.0-b190-el8-aarch64.tar.gz
else
	tar -xf yugabyte-2.19.0.0-b190-linux-x86_64.tar.gz
fi
cd yugabyte-2.19.0.0
./bin/post_install.sh
cd ~
echo "#!/bin/sh
cd yugabyte-2.19.0.0
java -jar ../yb-sample-apps-141.jar \$@ --nodes 127.0.1.1:9042 > \$LOG_FILE 2>&1" > yugabytedb
chmod +x yugabytedb
