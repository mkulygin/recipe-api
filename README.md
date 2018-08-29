#RecipeApi description

##Starting a docker container

Use Dockerfile for starting a container.
Try to connect to container and run commands manually, if something does not work (commands after # Add permissions for www-data in Dockerfile).

**Authentification**

For authentification API uses JWT token.
Auth URL (POST) localhost/auth with login and password in body.
Testing login mkulygin and password tevakeku

Use data.sql and schema.sql in PostreSQL-container if needed.

**Testing**

PhpUnit tests are located in tests directory. 

To run them use bash and command:

./vendor/bin/phpunit --testdox --bootstrap vendor/autoload.php ./tests/AuthTest

**Seeding**

psql -h postgres -U hellorecipe -p 5432 -W hellorecipe

SELECT * FROM users;

INSERT INTO users (id, login, password) VALUES (1, 'mkulygin', 'cd779868d1558b852b8f172153b382c9');

INSERT INTO rating (id, recipeId, rate) VALUES (1, '5b69487f7af19c32f62da886', 5);



##Endpoints

For testing endpoint you can use Postman. Just import Recipes Api.postman_collection.json, make Auth request, take Bearer token in header. Now you are able to send requests to Recipe API with JWT token. See **https://www.getpostman.com/collections/f5c5c8e0aa9a391014f0** for all endpoints.


**List** uses pagination for listing requests in header's Link parameter.

**Create** endpoint response with new recipe's Id and URI for GET request to get the recipe.

**Get** just get a recipe with {id}. For example, localhost/recipes/5b697dff7af19c32f50c5647

**Update** update (PUT) or update partly (PATCH) some recipe with {id} 

**Delete** delete one recipe with {id}

**Rate** save rate for recipe with {id}. Parameter "rate" must be in body of POST request.

**Search** see Search part. 


##### Recipes

| Name   | Method      | URL                    | Protected |
| ---    | ---         | ---                    | ---       |
| List   | `GET`       | `/recipes`             | ✘         |
| Create | `POST`      | `/recipes`             | ✓         |
| Get    | `GET`       | `/recipes/{id}`        | ✘         |
| Update | `PUT/PATCH` | `/recipes/{id}`        | ✓         |
| Delete | `DELETE`    | `/recipes/{id}`        | ✓         |
| Rate   | `POST`      | `/recipes/{id}/rating` | ✘         |

###Search

You can use GET requests to /recipes/search/ with different 4 parameters. Two or more parameters use AND logic.

preptime=120 minutes (preparation time, value is string)

vegeterian=1 (a vegeterian recipe, value is 0 or 1)

difficulty=1 (the complexity of a recipe, value from 1 to 3)

name=Borsch (the name of a recipe)

###Rating
Use POST /recipes/5b69487f7af19c32f62da886/rating/ with "rate" in body. Rate must be in 1-5.


##Notes
No https support for now. Need more time for configurating docker. In addition, some manual commands from Dockerfile should be executed. See commands after "# Add permissions for www-data" in Dockerfile).

###THANK YOU!