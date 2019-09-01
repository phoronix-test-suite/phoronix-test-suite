#!/bin/bash

cat > gpu-residency << EOT
#!/bin/sh

if [[ -e \$1 ]]; then
  ST=\`cat \$1\`
  sleep 1m
  ET=\`cat \$1\`
  echo \$((ET - ST)) > \$LOG_FILE 2>&1
else
  echo -1 > ~/test-exit-status 2>&1
fi
EOT

chmod +x gpu-residency
