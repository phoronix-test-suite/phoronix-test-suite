#!/bin/sh

rm -rf RayTracingInVulkan-r6

tar -xf RayTracingInVulkan-r6.tar.gz
tar -xf vulkansdk-linux-x86_64-1.2.162.1.tar.gz

cd 1.2.162.1/
source setup-env.sh 
./vulkansdk

mkdir ~/RayTracingInVulkan-r6/src/vulkan
mkdir ~/RayTracingInVulkan-r6/src/Vulkan/vulkan

cp -va x86_64/include/vulkan/*.h ~/RayTracingInVulkan-r6/src/vulkan
cp -va x86_64/include/vulkan/*.h ~/RayTracingInVulkan-r6/src/Vulkan/vulkan

cd ~/RayTracingInVulkan-r6/
./vcpkg_linux.sh
./build_linux.sh
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd RayTracingInVulkan-r6/build/linux/bin/
./RayTracer \$@ > \$LOG_FILE" > rtiv
chmod +x rtiv
