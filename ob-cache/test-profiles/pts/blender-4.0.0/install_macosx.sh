#!/bin/sh
unzip -o cycles_benchmark_20160228.zip
mv benchmark/bmw27/*.blend ~
mv benchmark/classroom/*.blend ~
mv benchmark/fishy_cat/*.blend ~
mv benchmark/pabellon_barcelona/*.blend ~
echo "#!/bin/bash
[ ! -d /Volumes/Blender/ ] && hdid blender-4.0.0-macos-x64.dmg
BLEND_ARGS=\$@
if [[ \$@ =~ .*CPU.* ]]
then
	BLEND_ARGS=\${BLEND_ARGS/_gpu/_cpu}
fi
cd benchmark
/Volumes/Blender/Blender.app/Contents/MacOS/Blender \$BLEND_ARGS > \$LOG_FILE
rm -f output.test" > blender
chmod +x blender
