#!/bin/sh

tar -xjf mplayer-2009-06-04.tar.bz2

mkdir $HOME/mplayer_

cd mplayer-checkout-2009-06-04/

if [ -f "/usr/include/vdpau/vdpau.h" ]
then
	VDPAU_STATUS="--enable-vdpau"
else
	VDPAU_STATUS=""
fi

./configure --enable-xv --enable-xvmc $VDPAU_STATUS --disable-ivtv --prefix=$HOME/mplayer_ > /dev/null
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf mplayer-checkout-2009-06-04/

ln -s mplayer_/bin/mplayer mplayer
ln -s mplayer_/bin/mencoder mencoder
