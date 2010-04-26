#!/bin/sh

tar -xjf xplane_945_timedemo.tar.bz2

echo "#!/bin/sh
cd xplane_945_timedemo/

case \$OS_TYPE in
	\"MacOSX\" )
	./X-Plane.app/Contents/MacOS/X-Plane \$@
	;;
	* )
	./X-Plane-i686 \$@
	;;
esac

mv Log.txt \$LOG_FILE" > xplane9
chmod +x xplane9
