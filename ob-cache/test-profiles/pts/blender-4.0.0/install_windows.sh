#!/bin/sh
unzip -o blender-4.0.0-windows-x64.zip
unzip -o cycles_benchmark_20160228.zip
mv benchmark/bmw27/*.blend ~
mv benchmark/classroom/*.blend ~
mv benchmark/fishy_cat/*.blend ~
mv benchmark/pabellon_barcelona/*.blend ~
rm -rf benchmark
echo "#!/bin/bash
cd blender-4.0.0-windows-x64
BLEND_ARGS=\$@
if [[ \$@ =~ .*CPU.* ]]
then
	BLEND_ARGS=\${BLEND_ARGS/_gpu/_cpu}
fi
export HOME=\"\$DEBUG_HOME\"
export PATH=\$DEBUG_PATH
./blender.exe \$BLEND_ARGS > \$LOG_FILE
rm -f output.test" > blender
chmod +x blender
