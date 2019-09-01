#!/bin/sh

tar -xvjf MandelGPU-v1.3pts-1.tar.bz2
cd MandelGPU-v1.3pts/

case $OS_TYPE in
	"MacOSX")
		CCFLAGS="-O3 -lm -ftree-vectorize -funroll-loops -Wall -framework OpenCL -framework OpenGL -framework GLUT"
	;;
	*)
		CCFLAGS="-O3 -lm -ftree-vectorize -funroll-loops -Wall -lglut -lglut -lOpenCL -lGL"
	;;
esac

gcc -o mandelGPU mandelGPU.c displayfunc.c $CCFLAGS
echo $? > ~/install-exit-status
cpp <rendering_kernel.cl >preprocessed_rendering_kernel.cl
cpp <rendering_kernel_float4.cl >preprocessed_rendering_kernel_float4.cl

cd ~/

echo "#!/bin/sh
cd MandelGPU-v1.3pts/
./mandelGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mandelgpu
chmod +x mandelgpu
