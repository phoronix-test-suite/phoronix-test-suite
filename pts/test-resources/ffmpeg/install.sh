#!/bin/sh

if [ ! -f ../pts-shared/pts-trondheim.avi ]
  then
     tar -xvf ../pts-shared/pts-trondheim-avi.tar.bz2 -C ../pts-shared/
fi

tar -xjf ffmpeg-may27-2008.tar.bz2

cd ffmpeg-may27-2008/
./configure > /dev/null
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh

echo \"#!/bin/sh
./ffmpeg-may27-2008/ffmpeg -i ../pts-shared/pts-trondheim.avi -y -target ntsc-vcd /dev/null\" > encode-process
chmod +x encode-process

/usr/bin/time -f \"Encoding Time: %e Seconds\" ./encode-process 2>&1 | grep Seconds" > ffmpeg
chmod +x ffmpeg
