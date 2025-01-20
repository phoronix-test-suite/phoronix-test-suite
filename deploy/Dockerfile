FROM ubuntu:latest
ARG DEBIAN_FRONTEND=noninteractive
RUN apt update
RUN apt-get install -y unzip php-cli apt-utils mesa-utils php-xml git-core apt-file sudo vim
RUN apt-get install -y podman

# Copy the local phoronix-test-suite directory to the container
COPY . /phoronix-test-suite/