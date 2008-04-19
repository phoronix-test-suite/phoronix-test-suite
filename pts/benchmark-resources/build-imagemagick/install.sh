#!/bin/sh

cd $1

if [ ! -f image-magick.tar.bz2 ]
  then
     wget ftp://ftp.imagemagick.org/pub/ImageMagick/ImageMagick-6.4.0-7.tar.bz2 -O image-magick.tar.bz2
fi

echo "#!/bin/sh

if [ ! -f image-magick.tar.bz2 ]
  then
	echo \"Image Magick Not Downloaded... Build Fails.\"
	exit
fi

rm -rf ImageMagick-6.4.0/
tar -xjf image-magick.tar.bz2
cd ImageMagick-6.4.0/
./configure > /dev/null
sleep 3
/usr/bin/time -f \"ImageMagick Build Time: %e Seconds\" make -s -j \$NUM_CPU_JOBS 2>&1 | grep Seconds" > time-compile-imagemagick

chmod +x time-compile-imagemagick
