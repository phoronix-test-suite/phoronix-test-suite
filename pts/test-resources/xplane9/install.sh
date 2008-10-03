#!/bin/sh

tar -xjf X-Plane_900r3_timedemo.tar.bz2

echo "#!/bin/sh
cd X-Plane_900r3_timedemo/

case \$OS_TYPE in
	\"MacOSX\" )
	./X-Plane.app/Contents/MacOS/X-Plane \$@ > /dev/null
	;;
	* )
	./X-Plane-i686 \$@ > /dev/null
	;;
esac

grep FRAMERATE Log.txt" > xplane9
chmod +x xplane9
