#!/bin/sh

# Installation

chmod +x ut2004-lnx-demo3334.run

./ut2004-lnx-demo3334.run --noexec --target .

tar -zxvf UT2004-ptsconfig-2.tar.gz
tar xvfj ut2004demo.tar.bz2

case $OS_ARCH in
	"x86_64" )
	tar xvfj linux-amd64.tar.bz2
	;;
	* )
	tar xvfj linux-x86.tar.bz2
	;;
esac
mv -f System/* System/

echo "#!/bin/sh
cd System/
./ut2004-bin \$@
mv ~/.ut2004demo/Benchmark/benchmark.log \$LOG_FILE" > ut2004-demo
chmod +x ut2004-demo

echo "causeevent flyby
ship" > flybyexec.txt
# mkdir -p ~/.ut2004demo/System
# cp -f UT2004.ini ~/.ut2004demo/System

