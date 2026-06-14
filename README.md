# Despliegue de Entorno Web Local con Nginx, PHP y MariaDB usando Docker
## Descripción del Proyecto
    Levantamiento de un entorno local para PHP con base de datos
## Arquitectura del Entorno
### 1. Servidor Web (Nginx)
    Nginx es un servicio que nos permite mostrar nuestro entorno en nuestro local host
### 2. Procesador de Lenguaje (PHP-FPM)
    PHP-FPM es el interprete del lenguaje, Nginx ahora puede entender, procesar y mostrar codigo php en la pagina que, de otra manera, mostraria como texto plano
### 3. Motor de Base de Datos (MariaDB)
    Base de datos SQL de codigo abierto
## Prerrequisitos y Dependencias
    Se asume que docker ya esta instalado al momento de realizar el levantamiento de este entorno.
    En caso no este instalado visitar: https://docs.docker.com/engine/install/
## Gestión de Seguridad (Variables de Entorno)
    Evitando exponer credenciales o codigo sensible, se utiliza un archivo .env el cual contiene las credenciales de acceso a la base de datos.
    Este archivo esta protegido detras de un .gitignore, guardando unicamente de forma local las credenciales, protegiendo de cualquier filtrado accidental de estas.
## 📦 Instrucciones de Despliegue Rápido
### 1. Clonar el repositorio y preparar los archivos
    `git clone https://github.com/Briel301/Docker_PHP_Nginx`
### 2. Configurar las variables locales (.env)
    crear un archivo .env con las credenciales necesarias, plantilla recomendada, mas no obligatoria:
    `# Configuración de la Base de Datos
    MariadbRootPassword=
    MariadbDatabase=
    MariadbUser=
    MariadbPassword=

    # Configuración para despliegue de errores
    AppEnvironment=Development `

### 3. Levantar los servicios con Docker Compose
    `sudo docker compose up -d`
## Ciclo de Vida de la Base de Datos y Persistencia
    El guardado de la base de datos se hace en local y no dentro del contenedor docker, si bien, esto es perfecto por la persistencia de datos, al momento de realizar cambios en el docker-compose.yml en la seccion "db", por seguridad, estos cambios no se aplicaran, asi que si se necesita cambiar las credenciales, podemos seguir estos dos escenarios:
### Escenario 1: Reinicialización Completa (Realizar solo en entorno de Desarrollo)
    Cuando la base de datos aun esta vacia o con datos placeholder, lo mas rapido es realizar un 
    `sudo docker compose down -v `
    y seguidamente eliminar la carpeta "mariadb_data".
    esto nos da un entorno limpio y al iniciar nuevamente docker con 
    `sudo docker compose up -d`
    la carpeta se creara nuevamente con nuestras nuevas credenciales
### Escenario 2: Actualización de Credenciales en produccion(usando CLI de MariaDB)
    Si ya se encuentra el produccion el codigo, o ya hay datos en la DB que no puedan ser eliminados, lo mejor es realizar estos cambios directamente desde CLI con MariaDB
    
    Ingresamos a la terminal interactiva con
    ` docker exec -it `MariadbContainer` mariadb -u root -p `
    Una vez adentro (nos pide la contraseña del usuario root) ejecutamos el cambio de contraseña con SQL
    `ALTER USER 'usuario'@'localhost' IDENTIFIED BY 'NuevaContraseña';
    FLUSH PRIVILEGES;
    EXIT;`
## Diagnóstico y Resolución de Problemas (Troubleshooting / Lecciones Aprendidas)
### 1. Error: `Fatal error: Uncaught Error: Call to undefined function mysqli_connect()`
Mi error: Al intentar cargar la página web, PHP lanza un error crítico indicando que no reconoce la función de conexión a la base de datos.
Sucedio porque: Las imágenes oficiales de PHP en Docker vienen unicamente con lo necesario. No incluyen extensiones de bases de datos por defecto para optimizar el peso del contenedor.
Mi solución: añadí la instrucción `docker-php-ext-install mysqli` en la sección `command` del servicio de PHP dentro del `docker-compose.yml` para compilar la extensión en tiempo real durante el arranque.

### 2. Error: `php_network_getaddresses: getaddrinfo for [Nombre] failed: Name or service not known`
Mi erro: PHP no logra establecer la conexión y reporta que el Host no es reconocido en la red.
Sucedio porque: En entornos tradicionales (como XAMPP), los servicios se buscan en `localhost` o `127.0.0.1`. En Docker, cada contenedor vive en una red aislada. Intentar usar el nombre de la Base de Datos (`MYSQL_DATABASE`) o el host local como dirección de red provoca un fallo de enrutamiento.
Mi solución: Para comunicarse entre contenedores dentro de la misma red de Docker Compose, se debe utilizar el nombre del servicio declarado en el archivo YAML (en este caso, `db`) como el Hostname en la función de conexión de PHP.

### 3. Error de Autenticación: `Access denied for user 'Admin'@'172.19.0.x' (using password: YES)`
Mi error: Se modificaron las credenciales en el archivo .env después de haber inicializado el entorno por primera vez. 
Sucedio porque: El contenedor de MariaDB almacena los datos de configuración de seguridad de forma persistente en el volumen local mariadb_data/. Por seguridad, ignorará cualquier cambio posterior en las variables de entorno del archivo YAML si el volumen ya existe.

Mi solución: Para aplicar los cambios en un entorno de desarrollo vacío, se destruyeron los contenedores con docker compose down -v, se eliminó manualmente la carpeta persistente mariadb_data/ y se levantaron los servicios de nuevo para forzar una inicialización limpia desde el .env.

### 4. Excepción del Sistema: `Connection refused`
Mi error: Intentar ingresar al local host apenas nginx habia levantado el local host.

Sucedio porque: Al reconstruir el entorno con una base de datos limpia, el contenedor de PHP inició de forma instantánea e intentó ejecutar la cadena de conexión inmediatamente. Al mismo tiempo, MariaDB aún se encontraba estructurando internamente el nuevo directorio de datos y levantando el socket del puerto 3306 (proceso que toma entre 10 y 20 segundos). Al no encontrar el puerto abierto, PHP rechazó la conexión.

Mi solución: Se añadio un check health para hacer que el servicio esperara a levantar hasta que MariaDB estuviera listo para cargar los datos, evitando el error de conexion rechazada.
