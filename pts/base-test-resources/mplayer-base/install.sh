#!/bin/sh

tar -xjf mplayer-vaapi-20100602-FULL.tar.bz2

mkdir $HOME/mplayer_

cd mplayer-vaapi-20100602/mplayer-vaapi
patch -p1 < ../patches/mplayer-vaapi.patch
patch -p1 < ../patches/mplayer-vaapi-gma500-workaround.patch
patch -p1 < ../patches/mplayer-vaapi-0.29.patch
patch -p1 < ../patches/mplayer-vdpau.patch

if [ -f "/usr/include/vdpau/vdpau.h" ]
then
	VDPAU_STATUS="--enable-vdpau"
else
	VDPAU_STATUS="--disable-vdpau"
fi

if [ -f "/usr/include/va/va.h" ]
then
	VAAPI_STATUS="--enable-vaapi"
else
	VAAPI_STATUS="--disable-vaapi"
fi

./configure --enable-xv --enable-xvmc $VAAPI_STATUS $VDPAU_STATUS --disable-ivtv --prefix=$HOME/mplayer_

case $OS_TYPE in
	BSD|Solaris)
		gmake -j $NUM_CPU_JOBS
		gmake install
	;;
	*)
		make -j $NUM_CPU_JOBS
		make install
	;;
esac

cd ~

rm -rf mplayer-vaapi-20100602/
rm -rf mplayer_/share/

ln -s mplayer_/bin/mplayer mplayer
ln -s mplayer_/bin/mencoder mencoder
