#!/bin/bash

export TESTS_TO_PRECACHE=""

# Ensure Docker on system
DIR_NAME=phoronix-pts-docker
mkdir -p ./$DIR_NAME/base/
OS_OUTER=`pwd`
cd $DIR_NAME/base
OS_ROOT_PATH=`pwd`

rm -rf phoronix-test-suite/
git clone https://github.com/phoronix-test-suite/phoronix-test-suite.git
cd phoronix-test-suite
rm -rf .git

# cache OpenBenchmarking.org metadata
export PTS_USER_PATH_OVERRIDE=$OS_ROOT_PATH/var/lib/phoronix-test-suite/
rm -f $PTS_USER_PATH_OVERRIDE
mkdir -p $PTS_USER_PATH_OVERRIDE
./phoronix-test-suite make-openbenchmarking-cache lean

# cache select tests
export PTS_DOWNLOAD_CACHE_OVERRIDE=$OS_ROOT_PATH/var/cache/phoronix-test-suite/download-cache/
mkdir -p $PTS_DOWNLOAD_CACHE_OVERRIDE
export PTS_DOWNLOAD_CACHING_PLATFORM_LIMIT=1
# ./phoronix-test-suite make-download-cache $TESTS_TO_PRECACHE
# ./phoronix-test-suite info 1809091-PTS-CLEARLIN01

rm -f $PTS_USER_PATH_OVERRIDE/core.pt2so

# cleanup
cd $OS_OUTER/$DIR_NAME

tar -C base -cf base.tar .
rm -f base.tar.xz
xz -v -T0 base.tar

cat > Dockerfile << EOF
FROM ubuntu:20.04
MAINTAINER Phoronix Media <commercial@phoronix-test-suite.com>
ADD base.tar.xz /
ARG DEBIAN_FRONTEND=noninteractive
RUN apt update
RUN apt install -y unzip php-cli apt-utils mesa-utils php-xml git-core apt-file sudo
RUN apt-file update
ENTRYPOINT ["/phoronix-test-suite/phoronix-test-suite"]
EOF

docker build -t $DIR_NAME .

# If no arguments are passed, default to 'shell'
if [ $# -eq 0 ]; then
    set -- "shell"
fi

# Run Docker with the provided script arguments or default to 'shell'
docker run -it --rm \
    --device /dev/dri:/dev/dri \
    --volume /tmp/.X11-unix:/tmp/.X11-unix \
    --volume $XDG_RUNTIME_DIR/$WAYLAND_DISPLAY:/run/user/$(id -u)/wayland-0 \
    --env DISPLAY=$DISPLAY \
    --env XDG_RUNTIME_DIR=$XDG_RUNTIME_DIR \
    --env WAYLAND_DISPLAY=$WAYLAND_DISPLAY \
    $DIR_NAME "$@"

# docker tag phoronix-pts-docker phoronix/pts
# docker push phoronix/pts

