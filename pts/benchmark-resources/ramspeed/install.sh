#!/bin/sh

cd $1

if [ ! -f ramspeed.tar.gz ]
  then
     wget http://www.alasir.com/software/ramspeed/ramspeed-2.5.1.tar.gz -O ramspeed.tar.gz
fi

tar -xvf ramspeed.tar.gz
cd ramspeed-2.5.1/
cat build.sh | grep -v 'read ANS' > build_pts.sh
chmod +x build_pts.sh
./build_pts.sh
ln ramspeed ../

