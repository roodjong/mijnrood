**Deprecation Notice:** This repository is no longer actively maintained.

# Setup

The docker-compose file creates four containers:
1. An nginx load balancer
2. A PHP server
3. A MariaDB database
4. A node container

Build and run these images with docker-compose:

`docker compose up --build -d`

The container will have appropriate volume bindings with `./app`.

`composer` is the dependency manager used for PHP. Install the dependencies on the PHP docker container:

`docker compose run --rm php82-service composer install`

Do the same for the node dependencies in the node container.
Do this after you install the composer dependencies as it mutates packages.json:

`docker compose run --rm node-service yarn install --force`

Create the database by executing:

`docker compose run --rm php82-service symfony console doctrine:database:create`

Create the schema with:

`docker compose run --rm php82-service symfony console doctrine:migrations:migrate`

Build some files needed for the frontend:

`docker compose run --rm node-service yarn build`

And finally populate the DB with some test data:

`docker compose run --rm php82-service symfony console doctrine:fixtures:load`

Create a new file `.env.local` and insert here the testing `MOLLIE_API_KEY`:

`echo MOLLIE_API_KEY=test_................................. > .env.local`

The default admin ID is 1, but be wary that each time you run the fixtures, this
ID will be incremented because of auto-increment options in the database.

Go to `http://localhost:8080/` and you should be greeted by the MijnRood login page.

You can log in with `admindebaas@example.com` as email, and `admin` as password.
Look at `src/DataFixtures/` to see an overview of all test data, including other accounts.

## Contributing

You can generate new migrations with:

`docker compose run --rm php82-service symfony console doctrine:migrations:diff`

Make sure the output is as you expected, and change it if necessary!

## License

This project is licensed under the EUPL, the license text in English can be found in the `LICENSE` file.

For more information about the EUPL and the license text in other languages, see their website: https://www.eupl.eu
