#!/bin/sh

cd $1
THIS_DIR=$(pwd)
unzip -o espeak-1.37-source.zip
cd espeak-1.37-source/src/
sed -i -e "s|/usr|$THIS_DIR/espeak_|g" Makefile
sed -i -e "s|/usr|$THIS_DIR/espeak_|g" speech.h
make -j $NUM_CPU_JOBS
make install
cd ../..
rm -rf espeak-1.37-source/

echo "#!/bin/sh
cd espeak_/bin/
LD_LIBRARY_PATH=$THIS_DIR/espeak_/lib/:\$LD_LIBRARY_PATH /usr/bin/time -f \"eSpeak Synthesis Time: %e Seconds\" ./espeak -f ../../20417-8.txt -w /dev/null 2>&1" > espeak
chmod +x espeak
