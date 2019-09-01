#!/bin/sh

if which blogbench>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: blogbench is not found on the system! This test profile needs a working installation in the PATH."
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
rm -rf \$HOME/scratch/
mkdir \$HOME/scratch/
blogbench -d \$HOME/scratch > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -rf \$HOME/scratch/" > blogbench
chmod +x blogbench
