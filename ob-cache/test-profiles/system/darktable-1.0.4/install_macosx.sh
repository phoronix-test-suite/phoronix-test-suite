#!/bin/sh

if [[ -x /Applications/darktable.app/Contents/MacOS/darktable-cli-bin ]] ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Darktable is not found on the system! This test profile needs a working Darktable installation in /Applications"
	echo 2 > ~/install-exit-status
fi

tar -xjvf darktable-bench-assets-1.tar.bz2
tar -xf server-rack.tar.xz

cd ~
echo "#!/bin/sh
rm -f output*.jpg
/Applications/darktable.app/Contents/MacOS/darktable-cli-bin \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status
/Applications/darktable.app/Contents/MacOS/darktable-cli-bin --version | head -n 1 | awk '{ print \$NF }' > ~/pts-test-version 2>/dev/null " > darktable
chmod +x darktable
