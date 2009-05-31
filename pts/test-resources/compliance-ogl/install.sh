#!/bin/sh

tar -xvf glew-1.5.0-src.tgz
cd glew/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ..

cat > compliance-ogl << 'EOT'
#!/bin/sh
LD_LIBRARY_PATH=glew/lib:$LD_LIBRARY_PATH ./glew/bin/glewinfo > $LOG_FILE
cat $LOG_FILE | grep GL_VERSION
EOT
chmod +x compliance-ogl
