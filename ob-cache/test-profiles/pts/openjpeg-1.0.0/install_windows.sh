#!/bin/sh

unzip -o openjpeg-v2.4.0-windows-x64.zip
chmod +x openjpeg-v2.4.0-windows-x64/bin/opj_compress.exe

echo "#!/bin/sh
./openjpeg-v2.4.0-windows-x64/bin/opj_compress.exe -threads \$NUM_CPU_CORES \$@ > \$LOG_FILE 2>&1
rm -f out.jp2" > openjpeg
chmod +x openjpeg
