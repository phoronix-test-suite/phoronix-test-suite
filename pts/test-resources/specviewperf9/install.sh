#!/bin/sh

cd $1

tar -xvf SPECViewperf9.0.3.tar.gz
cp -f SPECViewPerf9-Configure SPECViewperf9.0/src/Configure
chmod +x SPECViewperf9.0/src/Configure
cd SPECViewperf9.0/src
./Configure
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

	./Run_Viewset.csh 3dsmax-04 3dsmax results

	grep \"Mean\" sum_results/3dsmax/summary.txt
	;;
\"catia\")
	if [ ! -e sum_results/catia ]
	then
	    mkdir sum_results/catia
	fi

	./Run_Viewset.csh catia-02 catia results

	grep \"Mean\" sum_results/catia/summary.txt
	;;
\"ensight\")
	if [ ! -e sum_results/ensight ]
	then
	    mkdir sum_results/ensight
	fi

	./Run_Viewset.csh ensight-03 ensight results

	grep \"Mean\" sum_results/ensight/summary.txt
	;;
\"light\")
	if [ ! -e sum_results/light ]
	then
	    mkdir sum_results/light
	fi

	./Run_Viewset.csh light-08 light results

	grep \"Mean\" sum_results/light/summary.txt
	;;
\"maya\")
	if [ ! -e sum_results/maya ]
	then
	    mkdir sum_results/maya
	fi

	./Run_Viewset.csh maya-02 maya results

	grep \"Mean\" sum_results/maya/summary.txt
	;;
\"proe\")
	if [ ! -e sum_results/proe ]
	then
	    mkdir sum_results/proe
	fi

	./Run_Viewset.csh proe-04 proe results

	grep \"Mean\" sum_results/proe/summary.txt
	;;
\"sw\")
	if [ ! -e sum_results/sw ]
	then
	    mkdir sum_results/sw
	fi

	./Run_Viewset.csh sw-01 sw results

	grep \"Mean\" sum_results/sw/summary.txt
	;;
\"ugnx\")
	if [ ! -e sum_results/ugnx ]
	then
	    mkdir sum_results/ugnx
	fi

	./Run_Viewset.csh ugnx-01 ugnx results

	grep \"Mean\" sum_results/ugnx/summary.txt
	;;
\"tcvis\")
	if [ ! -e sum_results/tcvis ]
	then
	    mkdir sum_results/tcvis
	fi

	./Run_Viewset.csh tcvis-01 tcvis results

	grep \"Mean\" sum_results/tcvis/summary.txt
	;;
esac
" > specviewperf9
chmod +x specviewperf9
