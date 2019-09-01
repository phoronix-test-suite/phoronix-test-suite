#!/bin/sh

tar -xvjf JuliaGPU-v1.2pts-1.tar.bz2
cd JuliaGPU-v1.2pts/

case $OS_TYPE in
	"MacOSX")
		CCFLAGS="-O3 -march=native -ftree-vectorize -funroll-loops -Wall -framework OpenCL -framework OpenGL -framework GLUT"
	;;
	*)
		CCFLAGS="-O3 -march=native -ftree-vectorize -funroll-loops -Wall -lglut -lglut -lOpenCL -lGL"
	;;
esac

cc -o juliaGPU displayfunc.h camera.h vec.h renderconfig.h juliaGPU.c displayfunc.c $CCFLAGS -lm
echo $? > ~/install-exit-status
cpp <rendering_kernel.cl >preprocessed_rendering_kernel.cl
cd ~/

echo "#!/bin/sh
cd JuliaGPU-v1.2pts/
./juliaGPU \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > juliagpu
chmod +x juliagpu
