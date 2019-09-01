#!/bin/sh

tar -jxvf rodinia_2.4.tar.bz2

cd ~/rodinia_2.4/openmp/cfd; 				make;
cd ~/rodinia_2.4/openmp/lavaMD;				make;
cd ~/rodinia_2.4/openmp/leukocyte;  			make;
cd ~/rodinia_2.4/openmp/streamcluster;			make;
echo \$? > ~/install-exit-status

cd ~/rodinia_2.4/opencl/lavaMD;				make;
cd ~/rodinia_2.4/opencl/leukocyte;			make;
cd ~/rodinia_2.4/opencl/heartwall;			make;
cd ~/rodinia_2.4/opencl/myocyte;			make;
cd ~/rodinia_2.4/opencl/particlefilter;			make;

cd ~/
echo "#!/bin/sh

export OMP_NUM_THREADS=\$NUM_CPU_CORES

case \$@ in
	\"OMP_CFD\")
		cd ~/rodinia_2.4/openmp/cfd
		./euler3d_cpu_double ../../data/cfd/missile.domn.0.2M > \$LOG_FILE
	;;
	\"OMP_LAVAMD\")
		cd ~/rodinia_2.4/openmp/lavaMD
		./lavaMD -cores \$NUM_CPU_CORES -boxes1d 48 > \$LOG_FILE
	;;
	\"OMP_LEUKOCYTE\")
		cd ~/rodinia_2.4/openmp/leukocyte
		./OpenMP/leukocyte 60 \$NUM_CPU_CORES ../../data/leukocyte/testfile.avi > \$LOG_FILE
	;;
	\"OMP_STREAMCLUSTER\")
		cd ~/rodinia_2.4/openmp/streamcluster
		./sc_omp 10 30 512 65536 65536 2000 none output.txt \$NUM_CPU_CORES > \$LOG_FILE
	;;
	\"OCL_LAVAMD\")
		cd ~/rodinia_2.4/opencl/lavaMD
		./lavaMD -boxes1d 48 > \$LOG_FILE
	;;
	\"OCL_LEUKOCYTE\")
		cd ~/rodinia_2.4/opencl/leukocyte/OpenCL
		./leukocyte ../../../data/leukocyte/testfile.avi 590 > \$LOG_FILE
	;;
	\"OCL_HEARTWALL\")
		cd ~/rodinia_2.4/opencl/heartwall
		./heartwall 100 > \$LOG_FILE
		echo 0 > ~/test-exit-status
	;;
	\"OCL_MYOCYTE\")
		cd ~/rodinia_2.4/opencl/myocyte
		./myocyte.out -time 8000 > \$LOG_FILE
	;;
	\"OCL_PARTICLEFILTER\")
		cd ~/rodinia_2.4/opencl/particlefilter
		./OCL_particlefilter_double -x 128 -y 128 -z 10 -np 400000 > \$LOG_FILE
	;;
esac

echo \$? > ~/test-exit-status" > rodinia
chmod +x rodinia
