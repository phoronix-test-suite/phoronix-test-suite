#!/bin/sh

mkdir $HOME/lame_

tar -zxvf lame-3.100.tar.gz
cd lame-3.100/
./configure --prefix=$HOME/lame_ --enable-expopt=full
make
echo $? > ~/install-exit-status
make install
cd ..
rm -rf lame-3.100/

echo "#!/bin/sh
./lame_/bin/lame -h \$TEST_EXTENDS/pts-trondheim.wav /dev/null 2>&1
echo \$? > ~/test-exit-status" > lame
chmod +x lame
