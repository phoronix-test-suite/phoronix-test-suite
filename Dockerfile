FROM ubuntu:20.04 as light

LABEL org.opencontainers.image.authors="Phoronix Media <commercial@phoronix-test-suite.com>"


ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
  apt-file\
  apt-utils\
  git-core\
  php-cli\
  php-xml\
  sudo\
  unzip\
  && rm -rf /var/lib/apt/lists/*


WORKDIR /app/

# copy in files
COPY . /app/

RUN ./phoronix-test-suite make-openbenchmarking-cache lean \
  && rm -f /var/lib/phoronix-test-suite/core.pt2so

CMD ["./phoronix-test-suite", "shell"]


FROM light as full

# install extra packages commonly used by tests
RUN apt-get update && apt-get install -y \
  build-essential\
  autoconf\
  && rm -rf /var/lib/apt/lists/*
