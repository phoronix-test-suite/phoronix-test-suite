#!/bin/sh

if which openscad>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: OpenSCAD is not found on the system! This test profile needs a working openscad binary in the PATH"
	echo 2 > ~/install-exit-status
fi

unzip -o openscad-benchmark-2.zip

echo "#!/bin/sh
cd openscad-benchmark
openscad \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status
openscad -v > ~/pts-footnote 2>&1 " > openscad
chmod +x openscad
