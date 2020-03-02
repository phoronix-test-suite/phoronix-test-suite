#!/bin/sh

tar -xf smhasher-20200229.tar.xz
cd smhasher-git
./build.sh
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd smhasher-git
./SMHasher \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > smhasher
chmod +x smhasher
