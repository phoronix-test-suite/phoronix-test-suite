#!/bin/sh

if which inkscape>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: inkscape is not found on the system! This test profile needs the 'inkscape' command in the PATH"
	echo 2 > ~/install-exit-status
fi

unzip -o svg-test-files-1.zip

echo "#!/bin/sh
for i in *.svg
do
	inkscape --export-filename=output.png \$i
	echo \$? > ~/test-exit-status
done
inkscape --version 2>&1 | grep Inkscape > ~/pts-footnote

" > inkscape
chmod +x inkscape
