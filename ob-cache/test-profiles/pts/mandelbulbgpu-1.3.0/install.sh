#!/bin/sh

tar -xvjf mandelbulbGPU-v1.0pts-1.tar.bz2
cd mandelbulbGPU-v1.0pts/

case $OS_TYPE in
	"MacOSX")
		CCFLAGS="-O3 -lm -ftree-vectorize -funroll-loops -Wall -framework OpenCL -framework OpenGL -framework GLUT"
	;;
	*)
		CCFLAGS="-O3 -lm -ftree-vectorize -funroll-loops -Wall -lglut -lglut -lOpenCL -lGL"
	;;
esac

gcc -o mandelbulbGPU mandelbulbGPU.c displayfunc.c $CCFLAGS
echo $? > ~/install-exit-status
cpp <rendering_kernel.cl >preprocessed_rendering_kernel.cl

cd ~/

echo "#!/bin/sh
cd mandelbulbGPU-v1.0pts/
./mandelbulbGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mandelbulbgpu
chmod +x mandelbulbgpu
