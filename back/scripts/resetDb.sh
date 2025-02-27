#!/bin/bash

ENV=${1:-dev}

if [ "$ENV" == "test" ]; then
  ENV_ARG="--env=test"
elif [ "$ENV" == "dev" ]; then
  ENV_ARG="--env=dev"
else
  echo "Environnement inconnu : $ENV"
  echo "Utilisez 'dev' ou 'test'"
  exit 1
fi

# Delete the database if it exists
docker-compose exec -it php bash -c "php bin/console doctrine:database:drop --force $ENV_ARG"

# Create the database
docker-compose exec -it php bash -c "php bin/console doctrine:database:create $ENV_ARG"

# Play the migrations
docker-compose exec -it php bash -c "php bin/console doctrine:migrations:migrate $ENV_ARG"
