#!/bin/sh

chmod +x Unigine_Heaven-4.0.run
./Unigine_Heaven-4.0.run --nox11

echo "#!/bin/sh
cd Unigine_Heaven-4.0/
export LD_LIBRARY_PATH=bin/:\$LD_LIBRARY_PATH

UNIGINE_ARGS=\$@
if [[ \$UNIGINE_ARGS != *\"-video_fullscreen\"* ]]
then
	UNIGINE_ARGS=\"\$UNIGINE_ARGS -video_fullscreen 1\"
fi

if [ \$OS_ARCH = \"x86_64\" ]
then
	export LD_LIBRARY_PATH=./bin/x64/:\$LD_LIBRARY_PATH
	./bin/heaven_x64 -video_app opengl -data_path ../ -sound_app null -engine_config ../data/heaven_4.0.cfg -system_script heaven/unigine.cpp -video_mode -1 -extern_define PHORONIX,RELEASE \$UNIGINE_ARGS > \$LOG_FILE 2>&1
else
	export LD_LIBRARY_PATH=./bin/x86/:\$LD_LIBRARY_PATH
	./bin/heaven_x86 -video_app opengl -data_path ../ -sound_app null -engine_config ../data/heaven_4.0.cfg -system_script heaven/unigine.cpp -video_mode -1 -extern_define PHORONIX,RELEASE \$UNIGINE_ARGS > \$LOG_FILE 2>&1
fi" > unigine-heaven
chmod +x unigine-heaven

