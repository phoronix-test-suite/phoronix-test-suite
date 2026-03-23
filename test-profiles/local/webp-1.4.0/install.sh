#!/bin/sh
tar -xf libwebp-1.4.0.tar.gz
unzip -o sample-photo-6000x4000-1.zip
cd libwebp-1.4.0
./configure
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
    echo $? > ~/install-exit-status
else
	make -j $NUM_CPU_CORES
    echo $? > ~/install-exit-status
fi
cd ~
echo "#!/bin/sh
./libwebp-1.4.0/examples/cwebp sample-photo-6000x4000.JPG -o out.webp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > webp
chmod +x webp
