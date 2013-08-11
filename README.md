# Aaron4m - Mongo
The purpose of this module to create a mongodb mapper that is a straight replacement for the ZFCommons dbAbstract.
This easily allows you to utilise MongoDb while still easily using entities, hydrators and mappers as well as exposing
the native php client.

## Installation
Added the following requirement to your projects composer.json file.

```php
"aaron4m/mongo": "dev-master"
```

and run

php ./composer.phar update

## Usage
You will need to copy the db.local.php.dist file to your /config/autoload folder and fill in your database details.

For an example implementation, please see my ZfcUser MongoDB Plugin.
