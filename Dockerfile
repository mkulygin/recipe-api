FROM quay.io/hellofresh/php70:7.1

# Adds nginx configurations
ADD ./docker/nginx/default.conf   /etc/nginx/sites-available/default

# Environment variables to PHP-FPM
RUN sed -i -e "s/;clear_env\s*=\s*no/clear_env = no/g" /etc/php/7.1/fpm/pool.d/www.conf


# docker-compose up -d
# docker ps
# docker exec -it <CONTAINER ID> bash
# log error log of nginx-php: tail -f /var/log/nginx/error.log
# docker-compose down

# Set apps home directory.
ENV APP_DIR /server/http

# Adds the application code to the image
ADD . ${APP_DIR}

# Define current working directory.
WORKDIR ${APP_DIR}

# Add permissions for www-data
# TODO check installing - it's working in manual mode only under root
RUN chown -R www-data /server/http/web /server/http/ /server/
RUN chgrp -R www-data /server/http/web /server/http/ /server/
RUN chmod -R 755 /server/http/web/ /server/http/ /server/

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# JWT library
RUN composer require firebase/php-jwt
# unit tests library
RUN composer require --dev phpunit/phpunit ^6
RUN composer require doctrine/dbal
# install mongo driver
RUN cd vendor
RUN git clone https://github.com/mongodb/mongo-php-driver.git
RUN cd mongo-php-driver
RUN git submodule update --init
RUN phpize
RUN ./configure
RUN make all
RUN sudo make install
RUN echo "extension=mongodb.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
RUN service nginx reload
RUN service php7.1-fpm reload


#database abstraction library
# https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/introduction.html#using-php-7
RUN composer config "platform.ext-mongo" "1.6.16" && composer require "alcaeus/mongo-php-adapter"
RUN composer require doctrine/mongodb
RUN composer require doctrine/orm
RUN composer require doctrine/mongodb-odm
RUN composer require doctrine/mongodb-odm-bundle


# Cleanup
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

EXPOSE 80
