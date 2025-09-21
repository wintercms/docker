# Winter CMS in Docker

Run a production-ready installation of [Winter CMS](https://wintercms.com) in [Docker](https://docker.com). This Docker image has been built and configured on top of the [FrankenPHP](https://frankenphp.dev/) Docker image to provide a perfect environment for running Winter CMS.

## Features

- Winter installed and configured for production use.
- Lightning-fast FrankenPHP server using PHP 8.4, with all necessary extensions enabled for your database and cache engine.
- Automatic SSL certificates using the underlying Caddy server.
- Node.JS 24 installed for Winter Mix and Vite support.
- Composer installed for easily upgrading or installing new packages.
- Easily configurable using environment variables and Docker secrets.
- Easily persistable by mounting or binding certain folders, or simply bring your own Winter installation.

## Usage

The Winter Docker image runs on port 8000 by default. You can fire up a quick, non-persistent installation of Winter by using the following:

```bash
docker run --name winter -p 8000:8000 ghcr.io/wintercms/winter:latest
```

You can then access your Winter installation by navigating to **localhost:8000** in your web browser.

### Persisting data

If you would like to persist the Winter installation, you have two options:

- You can bind or mount volumes to the `/winter/plugins`, `/winter/storage` and `/winter/themes` directories, or
- You can bind an entire Winter installation to the `/winter` directory.

```bash
docker run --name winter -p 8000:8000 -v ./plugins:/winter/plugins -v ./storage:/winter/storage -v ./themes:/winter/themes ghcr.io/wintercms/winter:latest
# - OR -
docker run --name winter -p 8000:8000 -v ./winter:/winter ghcr.io/wintercms/winter:latest
```

> In both cases, you will need to ensure that the permissions are configured as necessary. The user inside the container is run with UID `10000` and GID `10000`.

In addition, the database must be persisted if you wish to keep the state of Winter. By default, this image uses an SQLite database stored in the `storage` folder, which will maintain state if you persist this directory using the methods above, but it is recommended that you use an external database such as MySQL, PostgreSQL or SQL Server and configure this image to use the external database.

### Running Artisan commands

This Docker image will automatically route calls to the Artisan console if your command starts with `artisan`. For example, to clear the cache, you could run the following:

```bash
docker run --name winter -p 8000:8000 ghcr.io/wintercms/winter:latest artisan cache:clear
```

> The `serve` command is blocked by default, since it makes little point to use it in this environment, but can be enabled by adding `--force` to the command immediately after `artisan serve`.

### Using a domain

If you have a domain pointing to a server running this Docker image, you can configure this Docker image to respond to the domain on the standard HTTP and HTTPS ports.

You will just need to configure the `SERVER_NAME` and `APP_URL` environment variables accordingly:

```bash
docker run --name winter -p 80:80 -p 443:443 --env "SERVER_NAME=my.domain.com" --env "APP_URL=https://my.domain.com" ghcr.io/wintercms/winter:latest
```

## Configuration

The following environment variables are available to [configure FrankenPHP](https://frankenphp.dev/docs/config/#environment-variables):

Variable | Description
-------- | -----------
`SERVER_NAME` | Change the [addresses on which to listen](https://caddyserver.com/docs/caddyfile/concepts#addresses), the provided hostnames will also be used for the generated TLS certificate. Defaults to `:8000`, listening to port `8000`. You should change this to your domain name if you wish to accept requests from a given domain.
`SERVER_ROOT` | Change the root directory of the site, defaults to `/winter/public`.
`CADDY_GLOBAL_OPTIONS` | Inject [global options](https://caddyserver.com/docs/caddyfile/options) to the underlying Caddy server.
`FRANKENPHP_CONFIG` | Inject config under the `frankenphp` directive.

In addition to the environment variables already made available by Winter, this Docker image also provides additional environment variables to control bootstrapping of the environment:

Variable | Description
-------- | -----------
`ACTIVE_THEME` | Sets the default active theme in Winter. Useful if you are providing your themes through a mounted volume. Defaults to the included `demo` theme.
`BACKEND_URI` | Defines the subdirectory in which the Backend can be reached. Defaults to the standard `backend` subdirectory.
`COMPOSER_UPDATE` | Set to `1` or `true` to run the `composer update` command on booting this image.
`COMPOSER_DEV` | If `COMPOSER_UPDATE` is used, this will also include "dev" dependencies if this is set to `1` or `true`.
`RUN_MIGRATIONS` | Set to `1` or `true` to run the `php artisan migrate` command on booting this image. This will automatically be run if using the default SQLite database and the database does not currently exist.
`ADMIN_PASSWORD` | If set, the `admin` user in the Backend that is automatically created on migration will have its password set to the one specified in this environment variable.
