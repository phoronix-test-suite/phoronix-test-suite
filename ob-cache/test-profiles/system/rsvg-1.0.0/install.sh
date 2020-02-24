#!/bin/sh

if which rsvg-convert>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: rsvg-convert is not found on the system! This test profile needs the 'rsvg-convert' command in the PATH"
	echo 2 > ~/install-exit-status
fi

unzip -o svg-test-files-1.zip
tar -xf W3C_SVG_11_TestSuite.tar.gz
cp svg/* .

echo "#!/bin/sh
for i in *.svg
do
	rsvg-convert -f png -o output.png \$i
	echo \$? > ~/test-exit-status
done

rsvg-convert --version 2>&1 | grep rsvg-convert > ~/pts-footnote

" > rsvg
chmod +x rsvg
