#!/bin/sh

unzip -o GeeXLab_Vulkan_Raytracing_Demo_v2021.2.18.0_linux64.zip

cd GeeXLab_Vulkan_Raytracing_Demo/
echo "#!/bin/sh
exit" > fake-browser
chmod +x fake-browser
ln -s fake-browser xdg-open
# workaround update version check

cd ~
echo "#!/bin/sh
cd GeeXLab_Vulkan_Raytracing_Demo/
rm -f _geexlab_log.txt
./Vulkan_Raytracing_Demo \$@ 
cat _geexlab_log.txt > \$LOG_FILE" > geexlab-rt
chmod +x geexlab-rt

