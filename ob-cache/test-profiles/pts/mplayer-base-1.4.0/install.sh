#!/bin/sh

tar -zxf MPlayer-1.3.0.tar.gz

mkdir $HOME/mplayer_

cd MPlayer-1.3.0

if [ -f "/usr/include/vdpau/vdpau.h" ]
then
	VDPAU_STATUS="--enable-vdpau"
else
	VDPAU_STATUS="--disable-vdpau"
fi

./configure --enable-xv $VAAPI_STATUS $VDPAU_STATUS --disable-ivtv --prefix=$HOME/mplayer_

case $OS_TYPE in
	BSD|Solaris)
		gmake -j $NUM_CPU_JOBS
		echo $? > ~/install-exit-status
		gmake install
	;;
	*)
		make -j $NUM_CPU_JOBS
		echo $? > ~/install-exit-status
		make install
	;;
esac

cd ~

rm -rf MPlayer-1.3.0
rm -rf mplayer_/share/

ln -s mplayer_/bin/mplayer mplayer
ln -s mplayer_/bin/mencoder mencoder
