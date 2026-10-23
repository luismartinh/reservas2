<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 Basic Project Template</h1>
    <br>
</p>

Yii 2 Basic Project Template is a skeleton [Yii 2](https://www.yiiframework.com/) application best for
rapidly creating small projects.

The template contains the basic features including user login/logout and a contact page.
It includes all commonly used configurations that would allow you to focus on adding new
features to your application.

[![Latest Stable Version](https://img.shields.io/packagist/v/yiisoft/yii2-app-basic.svg)](https://packagist.org/packages/yiisoft/yii2-app-basic)
[![Total Downloads](https://img.shields.io/packagist/dt/yiisoft/yii2-app-basic.svg)](https://packagist.org/packages/yiisoft/yii2-app-basic)
[![build](https://github.com/yiisoft/yii2-app-basic/workflows/build/badge.svg)](https://github.com/yiisoft/yii2-app-basic/actions?query=workflow%3Abuild)

DIRECTORY STRUCTURE
-------------------

      assets/             contains assets definition
      commands/           contains console commands (controllers)
      config/             contains application configurations
      controllers/        contains Web controller classes
      mail/               contains view files for e-mails
      models/             contains model classes
      runtime/            contains files generated during runtime
      tests/              contains various tests for the basic application
      vendor/             contains dependent 3rd-party packages
      views/              contains view files for the Web application
      web/                contains the entry script and Web resources



REQUIREMENTS
------------

The minimum requirement by this project template that your Web server supports PHP 7.4.


INSTALLATION
------------

### Install via Composer

If you do not have [Composer](https://getcomposer.org/), you may install it by following the instructions
at [getcomposer.org](https://getcomposer.org/doc/00-intro.md#installation-nix).

You can then install this project template using the following command:

~~~
composer create-project --prefer-dist yiisoft/yii2-app-basic basic
~~~

Now you should be able to access the application through the following URL, assuming `basic` is the directory
directly under the Web root.

~~~
http://localhost/basic/web/
~~~

### Install from an Archive File

Extract the archive file downloaded from [yiiframework.com](https://www.yiiframework.com/download/) to
a directory named `basic` that is directly under the Web root.

Set cookie validation key in `config/web.php` file to some random secret string:

```php
'request' => [
    // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
    'cookieValidationKey' => '<secret random string goes here>',
],
```

You can then access the application through the following URL:

~~~
http://localhost/basic/web/
~~~


### Install with Docker

Update your vendor packages

    docker-compose run --rm php composer update --prefer-dist
    
Run the installation triggers (creating cookie validation code)

    docker-compose run --rm php composer install    
    
Start the container

    docker-compose up -d
    
You can then access the application through the following URL:

    http://127.0.0.1:8000

**NOTES:** 
- Minimum required Docker engine version `17.04` for development (see [Performance tuning for volume mounts](https://docs.docker.com/docker-for-mac/osxfs-caching/))
- The default configuration uses a host-volume in your home directory `.docker-composer` for composer caches


CONFIGURATION
-------------

### Gemini API

Ambos clientes Gemini priorizan `GEMINI_API_KEY` del entorno del proceso
PHP. Las dos configuraciones Docker Compose cargan el archivo `.env` de la raíz
del repositorio mediante `env_file` en el servicio `php`. Para una instalación
nueva, copiá `.env.example` a `.env`, completá la nueva clave localmente y restringí
los permisos del archivo (`chmod 600 .env`). Desde la raíz, aplicá los cambios con
`docker compose up -d --no-deps --force-recreate php`.

No escribas la clave en archivos versionados ni en comandos que queden en el
historial. Después de modificar `.env` hay que recrear el contenedor; reiniciarlo
no actualiza su entorno. Fuera de Docker, configurá la variable en el proceso del
servidor web/PHP-FPM: la aplicación PHP no carga `.env` por sí misma.

#### Producción Apache/PHP-FPM con acceso FTP

Si la variable de entorno no existe, ambos clientes leen exclusivamente la entrada
`GEMINI_API_KEY` de `/home/vaqxtmgw/reservas.env`. No se usa `putenv`, no se ejecuta
el archivo y no se cargan otras variables. Una variable de entorno existente,
incluso vacía o inválida, tiene prioridad y no se reemplaza con el archivo.

1. Subí las versiones actualizadas de `components/GeminiClient.php`,
   `components/OpenAIClientGemini.php` y `components/OpenAIClientGeminiSql.php`
   a la carpeta de componentes de la aplicación desplegada.
2. Copiá `reservas.env.example` de la raíz del repositorio y llamá a la copia
   `reservas.env`. Completá `GEMINI_API_KEY` con la nueva clave, sin comentarios
   al final de la línea. Se aceptan valores sin comillas o entre comillas simples
   o dobles; no se admite interpolación ni claves repetidas.
3. Subí ese archivo a `/home/vaqxtmgw/reservas.env`, junto a `public_html`, nunca
   dentro de `/home/vaqxtmgw/public_html/reservas`.
4. Desde el cliente FTP, asignale permisos `600` si PHP corre con el mismo usuario.
   El proceso PHP debe poder leerlo y el hosting debe permitir esa ruta mediante
   `open_basedir`. Si el hosting bloquea el acceso, solicitá que habilite esa ruta
   privada; no muevas el archivo al directorio público.

El archivo se vuelve a leer en cada consulta: para rotar la clave solo actualizalo
por FTP; no hace falta reiniciar Apache/PHP-FPM. Si falta, no se puede leer o su
contenido no es válido, se devuelve el mismo error controlado de configuración.

Para comprobar la lectura sin consultar Gemini, subí también el controlador
`controllers/AgentController.php` actualizado. Iniciá sesión como administrador y
abrí `index.php?r=agent%2Fcheck-gemini-config` en la URL de entrada de tu aplicación.
Devuelve únicamente indicadores booleanos y el origen de la configuración. Para
confirmar el archivo privado deben ser `true`: `archivo_existe`,
`archivo_legible`, `lectura_correcta` y `clave_archivo_valida`.
`configuracion_valida` indica si la fuente seleccionada es válida; no comprueba
que Google acepte la clave. La comprobación no hace solicitudes HTTP ni imprime
claves o rutas internas, y solo permite acceso a administradores.

Si no hay una clave válida en la fuente de configuración seleccionada, ambos
clientes devuelven un error controlado antes de preparar el contexto o hacer HTTP.
El modelo usado es `gemini-3.6-flash`, reemplazo de `gemini-2.0-flash` retirado por
Google. Se conserva el formato de las respuestas exitosas y el límite de salida.
No se envían `temperature`, `topK` ni `topP`, obsoletos para Gemini 3.6.
La clave viaja
por HTTPS en `x-goog-api-key`, nunca en la URL.

No se guardan respuestas, preguntas, permisos, encabezados ni excepciones de
Gemini. El único diagnóstico nuevo es `runtime/gemini-diagnostics.log`, con
eventos fijos, estado HTTP y código numérico cURL. Mantené `runtime` fuera del
directorio público y aplicá la rotación de logs del servidor a ese archivo.
Los antiguos `components/debug_*.json` se conservan localmente, pero quedan
excluidos del seguimiento y no se actualizan desde los clientes Gemini.

### Configuración local de una instalación nueva

Los archivos locales `config/db.php` y `config/params-local.php` no se versionan.
Las instalaciones existentes conservan sus archivos y valores. Para una nueva:

```sh
cp config/db.example.php config/db.php
cp config/params-local.example.php config/params-local.php
```

Configurá los valores locales o las variables usadas por esos ejemplos:
`DB_DSN`, `DB_USER`, `DB_PASSWORD`, `SMTP_HOST`, `SMTP_USER`, `SMTP_PASSWORD`,
`SMTP_PORT` y `SMTP_ENCRYPTION`. Los datos bancarios se completan solo en el
archivo local cuando sean necesarios. Estas variables no cambian automáticamente
los archivos locales de instalaciones existentes.

### Database

Edit the file `config/db.php` with real data, for example:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    'username' => 'root',
    'password' => '1234',
    'charset' => 'utf8',
];
```

**NOTES:**
- Yii won't create the database for you, this has to be done manually before you can access it.
- Check and edit the other files in the `config/` directory to customize your application as required.
- Refer to the README in the `tests` directory for information specific to basic application tests.


TESTING
-------

Tests are located in `tests` directory. They are developed with [Codeception PHP Testing Framework](https://codeception.com/).
By default, there are 3 test suites:

- `unit`
- `functional`
- `acceptance`

Tests can be executed by running

```
vendor/bin/codecept run
```

The command above will execute unit and functional tests. Unit tests are testing the system components, while functional
tests are for testing user interaction. Acceptance tests are disabled by default as they require additional setup since
they perform testing in real browser. 

### Running tests with Docker

This project runs `php` and `mysql` inside `docker compose`. To avoid using the development database directly, the
recommended flow is:

```
# prepare reservas2_test from reservas2_1
../scripts/prepare_reservas2_test_db.sh

# run any Codeception suite inside the php container
../scripts/run_reservas2_tests.sh unit
../scripts/run_reservas2_tests.sh functional
```

There is also a `Makefile` at the repository root with shortcuts by domain:

```
make test-db-prepare
make test-fast
make test-smoke
make test-usuario-unit
make test-usuario-functional
make test-usuario-all
make test-tarifa-all
make test-cabana-all
make test-request-reserva-all
make test-reserva-all
make test-reservas-core-all
make test-dominios-all
```

Suggested usage:

```
# all tests for one bounded domain
make test-tarifa-all
make test-cabana-all
make test-request-reserva-all
make test-reserva-all

# quick feedback loops
make test-fast
make test-smoke

# all reservation-related domains together
make test-reservas-core-all

# all covered domains together
make test-dominios-all
```

`config/test_db.php` is configured to use the `reservas2_test` database on the Docker service `db`.

### Running  acceptance tests

To execute acceptance tests do the following:  

1. Rename `tests/acceptance.suite.yml.example` to `tests/acceptance.suite.yml` to enable suite configuration

2. Replace `codeception/base` package in `composer.json` with `codeception/codeception` to install full-featured
   version of Codeception

3. Update dependencies with Composer 

    ```
    composer update  
    ```

4. Download [Selenium Server](https://www.seleniumhq.org/download/) and launch it:

    ```
    java -jar ~/selenium-server-standalone-x.xx.x.jar
    ```

    In case of using Selenium Server 3.0 with Firefox browser since v48 or Google Chrome since v53 you must download [GeckoDriver](https://github.com/mozilla/geckodriver/releases) or [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/downloads) and launch Selenium with it:

    ```
    # for Firefox
    java -jar -Dwebdriver.gecko.driver=~/geckodriver ~/selenium-server-standalone-3.xx.x.jar
    
    # for Google Chrome
    java -jar -Dwebdriver.chrome.driver=~/chromedriver ~/selenium-server-standalone-3.xx.x.jar
    ``` 
    
    As an alternative way you can use already configured Docker container with older versions of Selenium and Firefox:
    
    ```
    docker run --net=host selenium/standalone-firefox:2.53.0
    ```

5. (Optional) Create `yii2basic_test` database and update it by applying migrations if you have them.

   ```
   tests/bin/yii migrate
   ```

   The database configuration can be found at `config/test_db.php`.


6. Start web server:

    ```
    tests/bin/yii serve
    ```

7. Now you can run all available tests

   ```
   # run all available tests
   vendor/bin/codecept run

   # run acceptance tests
   vendor/bin/codecept run acceptance

   # run only unit and functional tests
   vendor/bin/codecept run unit,functional
   ```

### Code coverage support

By default, code coverage is disabled in `codeception.yml` configuration file, you should uncomment needed rows to be able
to collect code coverage. You can run your tests and collect coverage with the following command:

```
#collect coverage for all tests
vendor/bin/codecept run --coverage --coverage-html --coverage-xml

#collect coverage only for unit tests
vendor/bin/codecept run unit --coverage --coverage-html --coverage-xml

#collect coverage for unit and functional tests
vendor/bin/codecept run functional,unit --coverage --coverage-html --coverage-xml
```

You can see code coverage output under the `tests/_output` directory.
