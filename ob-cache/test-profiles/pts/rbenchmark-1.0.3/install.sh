#!/bin/sh

tar -xjvf rbenchmarks-20160105.tar.bz2
echo $? > ~/install-exit-status
echo "#!/bin/sh
cd rbenchmarks
if which Rscript >/dev/null 2>&1 ;
then
	Rscript R-benchmark-25/R-benchmark-25.R \$@ > \$LOG_FILE
	echo \$? > ~/test-exit-status

	Rscript --version 2> ~/pts-footnote
else
	echo \"ERROR: Rscript is not found on the system!\"
	echo 2 > ~/test-exit-status
fi" > rbenchmark
chmod +x rbenchmark
