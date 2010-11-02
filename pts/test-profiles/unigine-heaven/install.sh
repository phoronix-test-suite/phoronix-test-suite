#!/bin/sh

chmod +x Unigine_Heaven-2.1.run
./Unigine_Heaven-2.1.run

echo "#!/bin/sh
cd Unigine_Heaven/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH

if [ \$OS_ARCH = \"x86_64\" ]
then
	./bin/Heaven_x64 -video_app opengl -data_path ../ -sound_app null -engine_config ../data/heaven_2.1.cfg -system_script heaven/unigine.cpp -video_mode -1 -video_fullscreen 1 -extern_define PHORONIX \$@ > \$LOG_FILE 2>&1
else
	./bin/Heaven_x86 -video_app opengl -data_path ../ -sound_app null -engine_config ../data/heaven_2.1.cfg -system_script heaven/unigine.cpp -video_mode -1 -video_fullscreen 1 -extern_define PHORONIX \$@ > \$LOG_FILE 2>&1
fi" > unigine-heaven
chmod +x unigine-heaven

