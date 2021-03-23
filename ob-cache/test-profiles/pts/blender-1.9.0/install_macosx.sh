#!/bin/sh

unzip -o cycles_benchmark_20160228.zip

mv benchmark/bmw27/*.blend ~
mv benchmark/classroom/*.blend ~
mv benchmark/fishy_cat/*.blend ~
mv benchmark/pabellon_barcelona/*.blend ~

echo "#!/bin/bash

[ ! -d /Volumes/Blender/ ] && hdid blender-2.92.0-macOS.dmg

BLEND_ARGS=\$@
if [[ \$@ =~ .*CUDA.* ]]
then
	COMPUTE_TYPE=\"CUDA\"
elif [[ \$@ =~ .*OPENCL.* ]]
then
	COMPUTE_TYPE=\"OPENCL\"
elif [[ \$@ =~ .*OPTIX.* ]]
then
	COMPUTE_TYPE=\"OPTIX\"
elif [[ \$@ =~ .*NONE.* ]]
then
	COMPUTE_TYPE=\"NONE\"
	BLEND_ARGS=\${BLEND_ARGS/_gpu/_cpu}
else
	COMPUTE_TYPE=\"NONE\"
	BLEND_ARGS=\${BLEND_ARGS/_gpu/_cpu}
fi

echo \"import bpy

bpy.context.preferences.addons['cycles'].preferences.get_devices()
bpy.context.preferences.addons['cycles'].preferences.compute_device_type = '\$COMPUTE_TYPE'
bpy.context.preferences.addons['cycles'].preferences.devices[0].use = True

bpy.ops.wm.save_userpref()\" > ~/blender-setgpu.py

/Volumes/Blender/Blender.app/Contents/MacOS/Blender -b --python \$DEBUG_HOME/blender-setgpu.py
cd benchmark
/Volumes/Blender/Blender.app/Contents/MacOS/Blender \$BLEND_ARGS > \$LOG_FILE
rm -f output.test" > blender
chmod +x blender
