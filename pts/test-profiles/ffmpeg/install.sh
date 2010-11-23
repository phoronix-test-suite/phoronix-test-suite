#!/bin/sh

tar -xjf ffmpeg-0.6.1.tar.bz2
mkdir ffmpeg_/

cd ffmpeg-0.6.1/
./configure --disable-zlib --prefix=$HOME/ffmpeg_/
make
echo $? > ~/install-exit-status
make install
cd ..
rm -rf ffmpeg-0.6.1/
rm -rf ffmpeg_/lib/

echo "#!/bin/sh

./ffmpeg_/bin/ffmpeg -i \$TEST_EXTENDS/pts-trondheim.avi -threads \$NUM_CPU_CORES -y -target ntsc-vcd /dev/null 2>&1
echo \$? > ~/test-exit-status" > ffmpeg
chmod +x ffmpeg
