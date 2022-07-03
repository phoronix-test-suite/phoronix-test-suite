#!/bin/sh


if which node>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Node is not found on the system! This test profile needs a working Node.js installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

if which npm>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Node package manager (NPM) is not found on the system! This test profile needs a working npm in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

npm install --prefix . fast-cli

echo "#!/bin/sh
node_modules/fast-cli/cli.js  --upload --json > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
" > fast-cli

chmod +x fast-cli
