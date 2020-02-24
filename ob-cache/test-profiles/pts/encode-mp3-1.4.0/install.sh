#!/bin/sh

mkdir $HOME/lame_

tar -zxvf lame-3.99.3.tar.gz
cd lame-3.99.3/
./configure --prefix=$HOME/lame_
make
echo $? > ~/install-exit-status
make install
cd ..
rm -rf lame-3.99.3/

echo "#!/bin/sh
./lame_/bin/lame -h \$TEST_EXTENDS/pts-trondheim.wav /dev/null 2>&1
echo \$? > ~/test-exit-status" > lame
chmod +x lame
