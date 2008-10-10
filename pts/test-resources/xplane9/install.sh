#!/bin/sh

tar -xjf X-Plane_900r3_timedemo.tar.bz2

echo "#!/bin/sh
cd X-Plane_900r3_timedemo/

case \$OS_TYPE in
	\"MacOSX\" )
	./X-Plane.app/Contents/MacOS/X-Plane \$@
	;;
	* )
	./X-Plane-i686 \$@
	;;
esac

grep FRAMERATE Log.txt > \$LOG_FILE" > xplane9
chmod +x xplane9
