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
	./Run_3dsmax.csh > /dev/null 2>&1
	cat results/3dsmax-04/*result.txt > \$LOG_FILE
	;;
\"catia\")
	./Run_catia.csh > /dev/null 2>&1
	cat results/catia-02/*result.txt > \$LOG_FILE
	;;
\"ensight\")
	./Run_ensight.csh > /dev/null 2>&1
	cat results/ensight-03/*result.txt > \$LOG_FILE
	;;
\"maya\")
	./Run_maya.csh > /dev/null 2>&1
	cat results/maya-02/*result.txt > \$LOG_FILE
	;;
\"proe\")
	./Run_proe.csh > /dev/null 2>&1
	cat results/proe-04/*result.txt > \$LOG_FILE
	;;
\"sw\")
	./Run_sw.csh > /dev/null 2>&1
	cat results/sw-01/*result.txt > \$LOG_FILE
	;;
\"tcvis\")
	./Run_tcvis.csh > /dev/null 2>&1
	cat results/tcvis-01/*result.txt > \$LOG_FILE
	;;
\"ugnx\")
	./Run_ugnx.csh > /dev/null 2>&1
	cat results/ugnx-01/*result.txt > \$LOG_FILE
	;;
esac" > specviewperf10
chmod +x specviewperf10
