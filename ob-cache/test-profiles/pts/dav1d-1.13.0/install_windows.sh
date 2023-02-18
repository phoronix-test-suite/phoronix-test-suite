#!/bin/sh
unzip -o ffmpeg-4.4-full_build.zip
./ffmpeg-4.4-full_build/bin/ffmpeg.exe -i Stream2_AV1_HD_6.8mbps.webm -vcodec copy -an -f ivf summer_nature_1080p.ivf
./ffmpeg-4.4-full_build/bin/ffmpeg.exe -i Stream2_AV1_4K_22.7mbps.webm -vcodec copy -an -f ivf summer_nature_4k.ivf
./ffmpeg-4.4-full_build/bin/ffmpeg.exe -i Chimera-AV1-8bit-1920x1080-6736kbps.mp4 -vcodec copy -an -f ivf chimera_8b_1080p.ivf
./ffmpeg-4.4-full_build/bin/ffmpeg.exe -i Chimera-AV1-10bit-1920x1080-6191kbps.mp4 -vcodec copy -an -f ivf chimera_10b_1080p.ivf
rm -rf ffmpeg-4.4-full_build
unzip -o dav1d-build-win64-1-1-0.zip
cd ~
echo "#!/bin/sh
./build/dav1d_install/bin/dav1d.exe \$@ --muxer null --threads \$NUM_CPU_CORES --filmgrain 0 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > dav1d
chmod +x dav1d
