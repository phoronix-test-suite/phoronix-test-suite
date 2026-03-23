#!/bin/sh

tar -zxvf smallpt-1.tar.gz


if [ $OS_TYPE = "BSD" ]
then
	export CXXFLAGS="$XXCFLAGS -Wno-narrowing -L/usr/local/lib"
fi
c++ -fopenmp -O3 $CXXFLAGS smallpt.cpp -o smallpt-renderer
echo $? > ~/install-exit-status

echo "#!/bin/sh
./smallpt-renderer 128 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > smallpt
chmod +x smallpt
