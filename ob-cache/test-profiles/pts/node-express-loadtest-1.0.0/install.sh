#!/bin/sh

unzip -o NodeRestPerfTest3-20181001.zip

if which node>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Node is not found on the system! This test profile needs a working Node.js installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi


cd NodeRestPerfTest3-master/
npm install express
npm install loadtest

cd ~
echo "#!/bin/sh
cd NodeRestPerfTest3-master/

node expressserver.js &

./node_modules/.bin/loadtest -n 100000 -c 250 http://localhost:8000 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

killall -9 node #cleanup this check

echo \"Nodejs \" > ~/pts-footnote
nodejs --version >> ~/pts-footnote 2>/dev/null" > node-express-loadtest
chmod +x node-express-loadtest
