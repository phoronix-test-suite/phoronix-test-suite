#!/bin/sh

tar -zxvf gutenberg-science.tar.gz
tar -xf espeak-1.48.04-source.tar.xz

cd espeak-1.48.04-source/src/
sed -i -e "s|/usr|$HOME/espeak_|g" Makefile
sed -i -e "s|/usr|$HOME/espeak_|g" speech.h
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ~
rm -rf espeak-1.48.04-source

echo "#!/bin/sh
cd espeak_/bin/
LD_LIBRARY_PATH=\$HOME/espeak_/lib/:\$LD_LIBRARY_PATH ./espeak -f ~/gutenberg-science.txt -w espeak-output 2>&1
echo \$? > ~/test-exit-status" > espeak
chmod +x espeak
