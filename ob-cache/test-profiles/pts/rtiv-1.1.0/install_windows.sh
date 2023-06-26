#!/bin/sh
7z x RayTracingInVulkan.r7.7z
echo $? > ~/install-exit-status
chmod +x RayTracingInVulkan.r7/bin/RayTracer.exe
echo "#!/bin/sh
cd RayTracingInVulkan.r7/bin/
./RayTracer.exe \$@ > \$LOG_FILE" > rtiv
chmod +x rtiv
