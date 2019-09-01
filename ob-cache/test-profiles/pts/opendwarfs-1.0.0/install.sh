#!/bin/sh

tar -xjvf OpenDwarfs-git-20131106.tar.bz2
cd OpenDwarfs-git-20131106
./autogen.sh
mkdir build
cd build

# disable-timing is needed otherwise there were compiler errors
../configure --with-apps=crc,csr,lud --disable-timing
make
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd OpenDwarfs-git-20131106/build/
./\$@ > \$LOG_FILE 2> /dev/null
echo \$? > ~/test-exit-status" > opendwarfs
chmod +x opendwarfs
