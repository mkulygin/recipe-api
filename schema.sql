CREATE TABLE users(id SERIAL PRIMARY KEY, login VARCHAR(30), password VARCHAR(32));
CREATE TABLE rating(id SERIAL PRIMARY KEY, recipeid VARCHAR(30), rating int);