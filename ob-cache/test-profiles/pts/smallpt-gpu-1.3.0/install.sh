#!/bin/sh

tar -xvjf SmallptGPU-v1.6pts-1.tar.bz2
cd SmallptGPU-v1.6pts/

case $OS_TYPE in
	"MacOSX")
		CCFLAGS="-O3 -lm -ftree-vectorize -funroll-loops -Wall -framework OpenCL -framework OpenGL -framework GLUT"
	;;
	*)
		CCFLAGS="-O3 -lm -ftree-vectorize -funroll-loops -Wall -lglut -lglut -lOpenCL -lGL"
	;;
esac

gcc -DSMALLPT_GPU -o smallptGPU simplernd.h vec.h camera.h geom.h displayfunc.h scene.h geomfunc.h smallptGPU.c displayfunc.c $CCFLAGS
echo $? > ~/install-exit-status

cpp <rendering_kernel.cl >preprocessed_rendering_kernel.cl
cpp <rendering_kernel_dl.cl >preprocessed_rendering_kernel_dl.cl
cd ~/

echo "#!/bin/sh
cd SmallptGPU-v1.6pts/
./smallptGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > smallpt-gpu
chmod +x smallpt-gpu
