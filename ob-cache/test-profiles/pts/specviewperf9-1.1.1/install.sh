#!/bin/sh

tar -zxvf SPECViewperf9.0.3.tar.gz
cd SPECViewperf9.0/src

ln -s libpng/png.h png.h

if [ "$OS_TYPE" = "Solaris" ]; then
 echo 4|./Configure
elif [ "$OS_ARCH" = "x86_64" ]; then
 echo 3|./Configure
else
 echo 1|./Configure
fi
cd ../..

echo "#!/bin/sh

cd SPECViewperf9.0/

if [ ! -e sum_results ]
	then
    mkdir sum_results
fi

if [ ! -e backup_results ]
	then
    mkdir backup_results
fi

case \"\$1\" in
\"3dsmax\")
	if [ ! -e sum_results/3dsmax ]
	then
	    mkdir sum_results/3dsmax
	fi

	./Run_Viewset.csh 3dsmax-04 3dsmax results > /dev/null 2>&1

	cat sum_results/3dsmax/summary.txt > \$LOG_FILE
	;;
\"catia\")
	if [ ! -e sum_results/catia ]
	then
	    mkdir sum_results/catia
	fi

	./Run_Viewset.csh catia-02 catia results > /dev/null 2>&1

	cat sum_results/catia/summary.txt > \$LOG_FILE
	;;
\"ensight\")
	if [ ! -e sum_results/ensight ]
	then
	    mkdir sum_results/ensight
	fi

	./Run_Viewset.csh ensight-03 ensight results > /dev/null 2>&1

	cat sum_results/ensight/summary.txt > \$LOG_FILE
	;;
\"light\")
	if [ ! -e sum_results/light ]
	then
	    mkdir sum_results/light
	fi

	./Run_Viewset.csh light-08 light results > /dev/null 2>&1

	cat sum_results/light/summary.txt > \$LOG_FILE
	;;
\"maya\")
	if [ ! -e sum_results/maya ]
	then
	    mkdir sum_results/maya
	fi

	./Run_Viewset.csh maya-02 maya results > /dev/null 2>&1

	cat sum_results/maya/summary.txt > \$LOG_FILE
	;;
\"proe\")
	if [ ! -e sum_results/proe ]
	then
	    mkdir sum_results/proe
	fi

	./Run_Viewset.csh proe-04 proe results > /dev/null 2>&1

	cat sum_results/proe/summary.txt > \$LOG_FILE
	;;
\"sw\")
	if [ ! -e sum_results/sw ]
	then
	    mkdir sum_results/sw
	fi

	./Run_Viewset.csh sw-01 sw results > /dev/null 2>&1

	cat sum_results/sw/summary.txt > \$LOG_FILE
	;;
\"ugnx\")
	if [ ! -e sum_results/ugnx ]
	then
	    mkdir sum_results/ugnx
	fi

	./Run_Viewset.csh ugnx-01 ugnx results > /dev/null 2>&1

	cat sum_results/ugnx/summary.txt > \$LOG_FILE
	;;
\"tcvis\")
	if [ ! -e sum_results/tcvis ]
	then
	    mkdir sum_results/tcvis
	fi

	./Run_Viewset.csh tcvis-01 tcvis results > /dev/null 2>&1

	cat sum_results/tcvis/summary.txt > \$LOG_FILE
	;;
esac
" > specviewperf9
chmod +x specviewperf9
