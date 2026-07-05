**Deprecation Notice:** This repository is no longer actively maintained.

# Setup

There are two ways to run the project locally: with [bougie](https://bougie.tools) (recommended, no Docker needed) or with docker-compose.

## Setup with bougie

Bougie manages the PHP toolchain, the composer dependencies, and the dev services (MariaDB and an HTTP server) for this project, so you don't need PHP, Composer, or a database installed. The PHP version and services are declared in `composer.json` under `extra.bougie`.

Install bougie:

`curl -LsSf https://bougie.tools/install.sh | sh`

Bring the project up. This installs PHP 8.1 with the required extensions, installs the composer dependencies, and starts the MariaDB and dev-server services:

`bougie start`

Bougie provisions a project-specific MariaDB database with generated credentials. These are only injected into `bougie run` commands, not into web requests, so write them to `.env.local` where Symfony picks them up everywhere:

```sh
bougie run -- sh -c 'echo "DATABASE_URL=\"mysql://$BOUGIE_SERVICE_MARIADB_USER:$BOUGIE_SERVICE_MARIADB_PASSWORD@localhost/$BOUGIE_SERVICE_MARIADB_DATABASE?serverVersion=mariadb-11.4.4&unix_socket=$BOUGIE_SERVICE_MARIADB_SOCKET\""' >> .env.local
```

Also add the testing `MOLLIE_API_KEY` to `.env.local`:

`echo MOLLIE_API_KEY=test_................................. >> .env.local`

The database itself is already created by bougie, so you only need to create the schema and load the test data:

```sh
bougie run -- bin/console doctrine:migrations:migrate
bougie run -- bin/console doctrine:fixtures:load
```

Bougie can also manage Node.js. Install it, then build the frontend (yarn is provided through corepack, pinned by the `packageManager` field in `package.json`):

```sh
bougie node install lts
bougie run -- corepack yarn install --force
bougie run -- corepack yarn build
```

Open the app with `bougie server open`, or go to the URL printed by `bougie start` (something like `http://mijnrood.bougie.run:7080`). You should be greeted by the MijnRood login page; test accounts are listed under [Local test login data](#local-test-login-data).

Any Symfony console command can be run through bougie, e.g.:

`bougie run -- bin/console doctrine:migrations:diff`

## Setup with docker-compose

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

## Local test login data:

Admin level:
- Name: `Admin de Baas`
- E-mail: `admindebaas@example.com`
- Password: `admin`

Group head level:
- Name: `Jan Jansen`
- E-mail: `janjansen@example.com`
- Password: `contact`
- Member off: `Noorderhaaks`
- Head off: `Noorderhaaks`

Member level:
- Name: `Henk de Vries`
- E-mail: `henkdevries@example.com`
- Password: `new_member`
- Member off: `Nooderhaaks`

## Ngrok for local payment testing

- Register an account at ngrok.com
- Install the ngrok client using your distributions package manager or download.ngrok.com/linux
- Find your authentication token at dashboard.ngrok.com/get-started/your-authtoken and register it with your client:
`ngrok config add-authtoken <YOUR AUTH TOKEN HERE>`
- Start ngrok:
`ngrok http 8080` (use port 7080 for the bougie setup)
- Set `COOKIE_DOMAIN` to the domain (without http(s)://) mentioned in the `Forwarding` row in `.env.local`:
`COOKIE_DOMAIN=<YOUR URL HERE>.ngrok-free.app`
- Open the URL mentioned in the `Forwarding` row instead of `localhost:8080`.

## Server deployment

To deploy updates on the server:
```
sudo -u deploy -i
docker compose -f docker/prod/docker-compose.yml --env-file .env.local down
git pull
docker compose -f docker/prod/docker-compose.yml --env-file .env.local up --build -d
```

If there are changed migrations, there is an extra step:
```
docker compose -f docker/prod/docker-compose.yml --env-file .env.local exec mijnrood_php bin/console doctrine:migrations:migrate
```

## Mollie data browser

A very basic ugly system for browsing the information from Mollie can be found at `/mollie-admin`.
This just does raw Mollie api requests and is only available for admins.

## Extra configuration

### Custom welcome mail

To add a custom welcome email, put the templates (html and plain text) in `templates/custom/email`.
Supported override templates are:
- `welcome.html.twig`, `welcome.html.txt.twig`
- `welcome_support-en.html.twig`, `welcome_support-en.txt.twig`
- `welcome_support-nl.html.twig`, `welcome_support-nl.txt.twig`
- `apply.html.twig`, `apply.txt.twig`
- `fresh_member.txt.twig`
- `contact_new_member.html.twig`, `contact_new_member.txt.twig`

## Contributing

You can generate new migrations with:

`docker compose run --rm php82-service symfony console doctrine:migrations:diff`

Make sure the output is as you expected, and change it if necessary!

## License

This project is licensed under the EUPL, the license text in English can be found in the `LICENSE` file.

For more information about the EUPL and the license text in other languages, see their website: https://www.eupl.eu

## Common Issues

- CSRF Token invalid? Can't login? This is usually caused by an incorrect `COOKIE_DOMAIN` setting in the environment.
