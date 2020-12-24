FROM php:8-alpine

RUN apk update && \
    apk upgrade && \
    apk add bash && \
    wget https://raw.githubusercontent.com/marcocesarato/PHP-Antimalware-Scanner/master/dist/scanner --no-check-certificate -O /usr/bin/scanner.phar && \
    printf "#!/bin/bash\nphp /usr/bin/scanner.phar \$@" > /usr/bin/scanner && \
    chmod u+x,g+x /usr/bin/scanner.phar && \
    chmod u+x,g+x /usr/bin/scanner && \
    export PATH=$PATH":/usr/bin"