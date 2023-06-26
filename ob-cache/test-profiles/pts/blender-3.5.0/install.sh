#!/bin/sh
tar -xf blender-3.5.0-linux-x64.tar.xz
unzip -o cycles_benchmark_20160228.zip
mv benchmark/bmw27/*.blend  ~
mv benchmark/classroom/*.blend ~
mv benchmark/fishy_cat/*.blend ~
mv benchmark/pabellon_barcelona/*.blend ~
rm -rf benchmark
echo "#!/bin/bash
cd blender-3.5.0-linux-x64
BLEND_ARGS=\$@
if [[ \$@ =~ .*CPU.* ]]
then
	BLEND_ARGS=\${BLEND_ARGS/_gpu/_cpu}
fi
./blender \$BLEND_ARGS > \$LOG_FILE 2> /dev/null
echo \$? > ~/test-exit-status
rm -f output.test" > blender
chmod +x blender
