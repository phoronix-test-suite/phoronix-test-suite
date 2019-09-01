#!/bin/sh

unzip -o benchmark-octane-20181001.zip


if which node>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Node is not found on the system! This test profile needs a working Node.js installation in the PATH."
	echo 2 > ~/install-exit-status
fi

cd ~
echo "#!/bin/sh
cd benchmark-octane-master
node run.js > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
echo \"Nodejs \" > ~/pts-footnote
nodejs --version >> ~/pts-footnote 2>/dev/null " > node-octane
chmod +x node-octane
