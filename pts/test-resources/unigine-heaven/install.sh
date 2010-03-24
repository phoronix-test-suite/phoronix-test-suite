#!/bin/sh

chmod +x Unigine_Heaven-2.0.run
./Unigine_Heaven-2.0.run

echo "#!/bin/sh
cd Unigine_Heaven/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH

if [ \$OS_ARCH = \"x86_64\" ]
then
	./bin/Heaven_x64 \$@ > \$LOG_FILE 2>&1
else
	./bin/Heaven_x86 \$@ > \$LOG_FILE 2>&1
fi" > unigine-heaven
chmod +x unigine-heaven

