#!/bin/sh

if which npm>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: NPM is not found on the system! This test profile needs a working Node.js installation with NPM in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

tar -xf web-tooling-benchmark-0.5.3.tar.gz
cd web-tooling-benchmark-0.5.3/
npm install
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd web-tooling-benchmark-0.5.3/

node dist/cli.js > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

echo \"Nodejs \" > ~/pts-footnote
node --version >> ~/pts-footnote 2>/dev/null" > node-web-tooling
chmod +x node-web-tooling
