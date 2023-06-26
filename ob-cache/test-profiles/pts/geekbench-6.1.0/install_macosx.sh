#!/bin/sh
unzip -o Geekbench-6.1.0-Mac.zip
echo "This test profile requires a GeekBench PRO license for command-line automation. Before running this test you must run:
          cd $HOME/
          ./Geekbench\ 6.app/Contents/Resources/geekbench6 -r <YOUR EMAIL> <YOUR LICENSE KEY>" > ~/install-message
echo "#!/bin/bash
././Geekbench\ 6.app/Contents/Resources/geekbench6 \$@ > \$LOG_FILE" > geekbench
chmod +x geekbench
