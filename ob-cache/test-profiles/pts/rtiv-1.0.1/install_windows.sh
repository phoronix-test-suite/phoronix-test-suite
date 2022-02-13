#!/bin/sh

7z x RayTracingInVulkan.r6.7z
echo $? > ~/install-exit-status
chmod +x RayTracingInVulkan.r6/bin/RayTracer.exe

echo "#!/bin/sh
cd RayTracingInVulkan.r6/bin/
./RayTracer.exe \$@ > \$LOG_FILE" > rtiv
chmod +x rtiv
