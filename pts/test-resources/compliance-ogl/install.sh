#!/bin/sh

cd $1

tar -xvf glew-1.5.0-src.tgz
cd glew/
make -j $NUM_CPU_JOBS
cd ..

echo "#!/bin/sh
./glew/bin/glewinfo | grep GL_VERSION" > compliance-ogl
chmod +x compliance-ogl

