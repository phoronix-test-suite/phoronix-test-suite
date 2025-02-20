FROM ubuntu:latest
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-update
RUN apt-get install -y unzip php-cli apt-utils mesa-utils php-xml git-core apt-file sudo vim 
RUN apt-get install -y podman
RUN apt-get install -y shadow-utils fuse-overlayfs

RUN useradd podman; \
echo podman:10000:5000 > /etc/subuid; \
echo podman:10000:5000 > /etc/subgid;

VOLUME /var/lib/containers
VOLUME /home/podman/.local/share/containers


RUN chown podman:podman -R /home/podman

# chmod containers.conf and adjust storage.conf to enable Fuse storage.
RUN mkdir -p /var/lib/shared/overlay-images /var/lib/shared/overlay-layers /var/lib/shared/vfs-images /var/lib/shared/vfs-layers; touch /var/lib/shared/overlay-images/images.lock; touch /var/lib/shared/overlay-layers/layers.lock; touch /var/lib/shared/vfs-images/images.lock; touch /var/lib/shared/vfs-layers/layers.lock
ENV _CONTAINERS_USERNS_CONFIGURED=""
COPY . /phoronix-test-suite/
