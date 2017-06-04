#!/bin/bash
set -e -o pipefail
for version in ${VERSIONS}; do
    _version=$(echo ${version} | cut -d ":" -f1)
    _short_version=$(echo ${_version//./} | cut -c 1,2 )
    _sha1=$(echo ${version} | cut -d ":" -f2)
    echo "=== Building Wordpress s2i v${_version}"
    oc new-build --name=wordpress-${_short_version} --code=https://github.com/hoolia/s2i-wordpress -e WORDPRESS_SHA1=${_sha1} -e WORDPRESS_VERSION=${_version} --to=wordpress:${_version}
done
    oc new-build --name=wordpress-${_short_version} --code=https://github.com/hoolia/s2i-wordpress -e WORDPRESS_SHA1=${_sha1} -e WORDPRESS_VERSION=${_version} --to=wordpress:latest

