#!/bin/sh
rm -rf RayTracingInVulkan-r7
tar -xf RayTracingInVulkan-r7.tar.gz
tar -xf vulkansdk-linux-x86_64-1.3.243.0.tar.gz
cd 1.3.243.0/
source setup-env.sh 
sed -i 's/python /python3 /g' vulkansdk
pip3 install --user jsonschema
./vulkansdk --skip-installing-deps --maxjobs
mkdir ~/RayTracingInVulkan-r7/src/vulkan
mkdir ~/RayTracingInVulkan-r7/src/Vulkan/vulkan
cp -va x86_64/include/vulkan/*.h ~/RayTracingInVulkan-r7/src/vulkan
cp -va x86_64/include/vulkan/*.h ~/RayTracingInVulkan-r7/src/Vulkan/vulkan
cd ~/RayTracingInVulkan-r7/
./vcpkg_linux.sh
./build_linux.sh
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd RayTracingInVulkan-r7/build/linux/bin/
./RayTracer \$@ > \$LOG_FILE" > rtiv
chmod +x rtiv
