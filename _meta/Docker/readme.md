# Cloudrexx Docker images #
This directory contains the Dockerfiles for all provided docker images (see [Dockerhub](https://hub.docker.com/r/cloudrexx/web/tags/)).

## Information about the tags ##
All images are based on the [official PHP images](https://hub.docker.com/_/php/) (apache variant). For each PHP version supported by Contrexx/Cloudrexx (v3.0 upwards, starting at PHP 5.6) there is a base image (f.e. PHP7.0). For each of these there is a "-with-mysql".

### With MySQL ###
The images suffixed with "-with-mysql" are based on the base images. In addition they contain the debian package "mysql-client". This can be useful for maintenance and debugging on systems without phpMyAdmin or any other access to the database server.

## Proposed setup ##
If there is no need to use the "-with-mysql" variant, we suggest using the base image to execute PHP. Please check the readme on https://github.com/Cloudrexx/cloudrexx for instructions on how to setup a Cloudrexx environment based on these images.

## Known issues ##
* The PHP 5.6 images do not support Memcached yet.
* Socket timeout is set to 600s which is way too high.
* PHP is rebuilt 3 times during image build. This could be reduced to once.

## (Re-)Build all images ##
In order to (re-)build all images, the following command can be used (from this directory):
```bash
find . -maxdepth 1 -type d \( ! -name . \) -exec bash -c 'cd "$1" && docker build -t cloudrexx/web:${1:2} .' _ {} \;
```
