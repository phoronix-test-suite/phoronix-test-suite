#!/bin/sh

tar -jxvf rodinia_3.1.tar.bz2

cd ~/rodinia_3.1/openmp/cfd; 				make;
cd ~/rodinia_3.1/openmp/lavaMD;				make;
cd ~/rodinia_3.1/openmp/leukocyte;  			make;
cd ~/rodinia_3.1/openmp/myocyte;  			make;
cd ~/rodinia_3.1/openmp/hotspot3D;  			make;
cd ~/rodinia_3.1/openmp/streamcluster;			make;
echo $? > ~/install-exit-status

cd ~/rodinia_3.1/opencl/lavaMD;				make;
cd ~/rodinia_3.1/opencl/leukocyte;			make;
cd ~/rodinia_3.1/opencl/heartwall;			make;
cd ~/rodinia_3.1/opencl/myocyte;			make;
cd ~/rodinia_3.1/opencl/particlefilter;			make;

export PATH=/usr/local/cuda/bin:$PATH
cd ~/rodinia_3.1/cuda/myocyte;			make;

cd ~/
echo "#!/bin/sh

export OMP_NUM_THREADS=\$NUM_CPU_CORES

case \$@ in
	\"OMP_CFD\")
		cd ~/rodinia_3.1/openmp/cfd
		./euler3d_cpu_double ../../data/cfd/missile.domn.0.2M > \$LOG_FILE
	;;
	\"OMP_LAVAMD\")
		cd ~/rodinia_3.1/openmp/lavaMD
		./lavaMD -cores \$NUM_CPU_CORES -boxes1d 96 > \$LOG_FILE
	;;
	\"OMP_LEUKOCYTE\")
		cd ~/rodinia_3.1/openmp/leukocyte
		./OpenMP/leukocyte 590 \$NUM_CPU_CORES ../../data/leukocyte/testfile.avi > \$LOG_FILE
	;;
	\"OMP_STREAMCLUSTER\")
		cd ~/rodinia_3.1/openmp/streamcluster
		./sc_omp 10 30 512 65536 65536 2000 none output.txt \$NUM_CPU_CORES > \$LOG_FILE
	;;
	\"OMP_MYOCYTE\")
		cd ~/rodinia_3.1/openmp/myocyte
		./myocyte.out 1000 200 0 \$NUM_CPU_CORES > \$LOG_FILE
	;;
	\"OMP_HOTSPOT3D\")
		cd ~/rodinia_3.1/openmp/hotspot3D
		./3D 512 8 10000 ../../data/hotspot3D/power_512x8 ../../data/hotspot3D/temp_512x8 output.out > \$LOG_FILE
	;;
	\"OCL_LAVAMD\")
		cd ~/rodinia_3.1/opencl/lavaMD
		./lavaMD -boxes1d 48 > \$LOG_FILE
	;;
	\"OCL_LEUKOCYTE\")
		cd ~/rodinia_3.1/opencl/leukocyte/OpenCL
		./leukocyte ../../../data/leukocyte/testfile.avi 590 > \$LOG_FILE
	;;
	\"OCL_HEARTWALL\")
		cd ~/rodinia_3.1/opencl/heartwall
		./heartwall 100 > \$LOG_FILE
		echo 0 > ~/test-exit-status
	;;
	\"OCL_MYOCYTE\")
		cd ~/rodinia_3.1/opencl/myocyte
		./myocyte.out -time 8000 > \$LOG_FILE
	;;
	\"OCL_PARTICLEFILTER\")
		cd ~/rodinia_3.1/opencl/particlefilter
		./OCL_particlefilter_double -x 128 -y 128 -z 10 -np 400000 > \$LOG_FILE
	;;
	\"CUDA_MYOCYTE\")
		cd ~/rodinia_3.1/cuda/myocyte
		./myocyte.out 1000 10 0 > \$LOG_FILE
	;;
esac

echo \$? > ~/test-exit-status" > rodinia
chmod +x rodinia
