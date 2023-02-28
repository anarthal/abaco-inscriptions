#!/bin/bash

docker start mysql8
docker start wordpress || docker run --name wordpress -p 8080:80 --network my-net -d -v $HOME/workspace/abaco-inscriptions:/var/www/html/wp-content/plugins/abaco wordpress

