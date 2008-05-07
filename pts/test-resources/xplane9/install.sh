#!/bin/sh

cd $1
tar -xjf X-Plane_900r3_timedemo.tar.bz2

echo "#!/bin/sh
cd X-Plane_900r3_timedemo/

./X-Plane-i686 \$@ > /dev/null
grep FRAMERATE Log.txt" > xplane9
chmod +x xplane9
