#!/bin/sh

tar -xf BasemarkGPU_ubuntu18_x64.tar.gz
echo "<?php

\$result = file_get_contents(\$argv[1]);
\$json = json_decode(\$result, TRUE);

echo 'AVERAGE FPS: ' . \$json['result']['averageFPS'] . PHP_EOL;
echo 'MINIMUM FPS: ' . \$json['result']['minFPS'] . PHP_EOL;
echo 'MAXIMUM FPS: ' . \$json['result']['maxFPS'] . PHP_EOL;
echo '---- GPU FRAMES ----' . PHP_EOL;
//echo implode(' ', \$json['frames']['frameTimes']);" > basemarkgpu-1.2.0/resources/parser.php

echo "#!/bin/bash
cd basemarkgpu-1.2.0/resources

./binaries/\$1 TestType Custom AssetPath assets/pkg TextureCompression bc7 ReportPath output.json BenchmarkMode true SkipZPrepass false Fullscreen true \$@
echo \$? > ~/test-exit-status

\$PHP_BIN parser.php output.json > \$LOG_FILE
" > basemark
chmod +x basemark
