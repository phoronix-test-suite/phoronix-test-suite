#!/bin/sh

tar -xvf SPECViewPerf10-Configure.tar.gz
tar -xvf SPECViewperf10.tgz

cp -f SPECViewPerf10-Configure SPECViewperf10/viewperf/viewperf10.0/src/Configure
chmod +x SPECViewperf10/viewperf/viewperf10.0/src/Configure
cd SPECViewperf10/viewperf/viewperf10.0/src/
./Configure
cd $1

echo "#!/bin/sh

cd SPECViewperf10/viewperf/viewperf10.0/

echo \"screenHeight  \$VIDEO_HEIGHT
screenWidth  \$VIDEO_WIDTH
threads \$NUM_CPU_CORES
\" > viewperf.config

case \"\$1\" in
\"3dsmax\")
	./Run_3dsmax.csh
	grep \"Composite Score\" results/3dsmax-04/*result.txt
	;;
\"catia\")
	./Run_catia.csh
	grep \"Composite Score\" results/catia-02/*result.txt
	;;
\"ensight\")
	./Run_ensight.csh
	grep \"Composite Score\" results/ensight-03/*result.txt
	;;
\"maya\")
	./Run_maya.csh
	grep \"Composite Score\" results/maya-02/*result.txt
	;;
\"proe\")
	./Run_proe.csh
	grep \"Composite Score\" results/proe-04/*result.txt
	;;
\"sw\")
	./Run_sw.csh
	grep \"Composite Score\" results/sw-01/*result.txt
	;;
\"tcvis\")
	./Run_tcvis.csh
	grep \"Composite Score\" results/tcvis-01/*result.txt
	;;
\"ugnx\")
	./Run_ugnx.csh
	grep \"Composite Score\" results/ugnx-01/*result.txt
	;;
esac" > specviewperf10
chmod +x specviewperf10
