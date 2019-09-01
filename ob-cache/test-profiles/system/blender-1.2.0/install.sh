#!/bin/sh

if which blender>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Blender is not found on the system! This test profile needs a working blender installation in the PATH."
	echo 2 > ~/install-exit-status
fi

unzip -o cycles_benchmark_20160228.zip

echo "#!/bin/bash
BLEND_ARGS=\$@
if [[ \$@ == *\"CUDA\"* ]]
then
	COMPUTE_TYPE=\"CUDA\"
elif [[ \$@ == *\"OPENCL\"* ]]
then
	COMPUTE_TYPE=\"OPENCL\"
elif [[ \$@ == *\"NONE\"* ]]
then
	COMPUTE_TYPE=\"NONE\"
	BLEND_ARGS=\${BLEND_ARGS/_gpu/_cpu}
else
	COMPUTE_TYPE=\"NONE\"
	BLEND_ARGS=\${BLEND_ARGS/_gpu/_cpu}
fi

echo \"import bpy

bpy.context.user_preferences.addons['cycles'].preferences.compute_device_type = '\$COMPUTE_TYPE'
bpy.context.preferences.addons['cycles'].preferences.compute_device_type = '\$COMPUTE_TYPE'
bpy.context.preferences.addons['cycles'].preferences.devices[0].use = True
bpy.context.user_preferences.system.compute_device_type = '\$COMPUTE_TYPE'
bpy.ops.wm.save_userpref()\" > setgpu.py

blender --version | cut -d \" \" -f 2 > ~/pts-test-version 2>/dev/null 

blender -b -P setgpu.py 
blender \$BLEND_ARGS > \$LOG_FILE 2> /dev/null
echo \$? > ~/test-exit-status
rm -f output.test" > blender
chmod +x blender
