#!/bin/sh

# FFmpeg install to demux AV1 WebM to IVF that can then be consumed by dav1d...
tar -xjf ffmpeg-4.1.3.tar.bz2
mkdir ffmpeg_/

cd ffmpeg-4.1.3/
./configure --disable-zlib --disable-doc --prefix=$HOME/ffmpeg_/
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~/

./ffmpeg_/bin/ffmpeg -i Stream2_AV1_HD_6.8mbps.webm -vcodec copy -an -f ivf summer_nature_1080p.ivf
rm Stream2_AV1_HD_6.8mbps.webm
./ffmpeg_/bin/ffmpeg -i Stream2_AV1_4K_22.7mbps.webm -vcodec copy -an -f ivf summer_nature_4k.ivf
rm Stream2_AV1_4K_22.7mbps.webm

rm -rf ffmpeg-4.1.3
rm -rf ffmpeg_

# Build Dav1d
tar -xf dav1d-0.3.0.tar.xz
cd dav1d-0.3.0
meson build --buildtype release
ninja -C build
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
./dav1d-0.3.0/build/tools/dav1d \$@ --muxer null --framethreads \$NUM_CPU_CORES --tilethreads 4 -q --filmgrain 0
echo \$? > ~/test-exit-status" > dav1d
chmod +x dav1d
