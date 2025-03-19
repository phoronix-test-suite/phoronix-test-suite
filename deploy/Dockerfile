FROM ubuntu:latest
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update
RUN apt-get install -y unzip php-cli apt-utils mesa-utils php-xml git-core apt-file sudo vim 
RUN apt-get install -y podman


RUN useradd podman; \
echo podman:10000:5000 > /etc/subuid; \
echo podman:10000:5000 > /etc/subgid;

VOLUME /var/lib/containers
VOLUME /home/podman/.local/share/containers


RUN chown podman:podman -R /home/podman

COPY . /phoronix-test-suite/
