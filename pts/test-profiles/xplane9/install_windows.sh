#!/bin/sh

unzip -o xplane_945_timedemo.zip

echo "#!/bin/sh
cd xplane_945_timedemo/

X-Plane.exe \$@
mv Log.txt \$LOG_FILE" > xplane9
