LukeT/PHP-Router
=========

A simple, drop-in PHP routing soloution.

  - Easy to call routes
  - Supports URL variables
 

### Installation
You're required to have PHP 5.4 or higher due to the use of short arrays.
The first way you can install is via including src/routes.php, although I don't recommend this.
The second way is via composer:
```sh
composer require luket/php-router dev-master
computer update
```
### Usage
Currently, use is fairly limited, but it gets the job done.
##### GET Requests 

```php
Router::GET("/home", ["name" => "ACosmeticName", "function" => "showHome"]);
```
The first paramater is the URI you want to be called on, function is the function that you want to be called. Alternativly you can use:

```php
Router::GET("/home", ["name" => "ACosmeticName", "controller" => "StaticPages@showAbout"]);
```
Which will call showAbout on the StaticPages class.

#####POST Requests

Post requests accept the same paramaters, accept they will only respond to a POST reqeust.
```php
Router::POST("/home", "controller" => "StaticController@handleHome");
```
In the future I may support PUT, DELETE, UPDATE etc.  
 
#####URL Paramaters
This also accepts URL paramates, so for instance http://example/enable/1001 would be:
```php
Router::GET("/[:alpha]/[:int]", ["function" => "doStuff()"]);
```
Currently accepted:

| URI           | Desc                                                              | Regex            | 
| ------------- |:-----------------------------------------------------------------:|:----------------:|
| [:int]        | Only allows an Interger as the paramater                          | ([a-zA-Z]+)
| [:alpha]      | Allows any alpha-numberic string, including a-Z, A-Z and 0-9      | ([0-9]+)
| [:string]     | Allows only letters (a-z, A-Z)                                    | ([a-zA-Z0-9-_]+)


------------

Make it work
--------------

Oh,Theres one thing you need to do to make it actually route.
```php
Router::run();
```

and thats all there is too it!
