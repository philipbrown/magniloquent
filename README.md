Magniloquent
============

Self-validating models for Laravel 4's Eloquent.

Based on the excellent [Ardent](https://github.com/laravelbook/ardent) package by [Max Ehsan](https://github.com/laravelbook).

This package is highly inspired by Ardent. I wanted to make some big changes and so I thought it would be better to start a new package rather than fundamentally change how Ardent validates data. If you are looking to extend Eloquent's functionality, you should also check out Ardent!

Magniloquent was extracted from [Cribbb](https://github.com/cribbb/cribbb).

##Installation
Add `magniloquent/magniloquent` as a requirement to `composer.json`:

```json
{
  "require": {
    "magniloquent/magniloquent": "dev-master"
  }
}
```

Update your packages with `composer update` or install with `composer install`.

##Getting Started
Magniloquent extends Eloquent rather than replaces it, and so to use Magniloquent, you need to extend your models like this:
```php
use Magniloquent\Magniloquent\Magniloquent;

class User extends Magniloquent{}
```
All of Eloquent's functionality is still available so you can continue to interact with your models as you normally would. If one of your models does not require validation, you don't have to use Magniloquent, you are free to mix and match.

##Validation Rules
For each model, you need to set validation rules that control what type of data can be inserted into the database. Generally you are free to do this wherever you want, but to use Magniloquent you should keep your rules inside the model.

Magniloquent uses Laravel's excellent [Validation](http://laravel.com/docs/validation) class so you defining your rules is really easy.

Your validation rules are simply stored as a static parameter and are seperated into `save`, `create` and `update` arrays:
```php
/**
 * Validation rules
 */
public static $rules = array(
  "save" => array(
    'username' => 'required|min:4',
    'email'    => 'required|email',
    'password' => 'required|min:8'
  ),
  "create" => array(
    'username'              => 'unique:users',
    'email'                 => 'unique:users',
    'password'              => 'confirmed',
    'password_confirmation' => 'required|min:8'
  ),
  "update" => array()
);
```
The `save` array are validation rules that are applicable whenever the model is changed. The `create` and `update` arrays are only added on their respective methods.

So in the example above, when a user is created, the username should be unique. When the user updates any of their information, the uniqueness validation test won't be applied.

Note: Magniloquent is able to correctly ignore the current object when validatings unique values.

##Easier Relationships
Defining relationships in Laravel can take up a ton of room in a model.  This can make reading and maintaining your models much more difficult.  Luckily, Magniloquent makes defining relationships a cinch.  Add a `$relationships` multi-dimensional array to your model.  Inside it, define the name of the relationship that will be called as the key and the value to be an array of parameters.  The first parameter is the type of relationship.  The rest are the parameters to be passed to that function.  Below is an example:

```php
class Athlete extends Magniloquent {

    protected static $relationships = array(
        'trophies' => array('hasMany', 'Trophy'),
        'team'     => array('belongsTo', 'Team', 'team_id'),
        'sports'   => array('belongsToMany', 'Sport', 'athletes_sports', 'athlete_id', 'sport_id')
    );

}
```

##Custom Purging
Magniloquent will automatically purge any attributes that start with an underscore `_` or end with `_confirmation`.  If you want to purge additional fields, add a `protected static $purgeable` array whose keys are the attributes to purge. Below is an example:

```php
class Account extends Magniloquent {

    protected static $purgeable = ['ssn'];

}
```

Anytime this model is saved, the `$ssn` attribute will be removed from the object before it is saved.  This allows you to run code the code below without worrying about inserting unnecessary data into the database.

```php
$account->save(Input::all());
```

##Custom Display Names
Magniloquent gives you the ability to customize the display name of each of the fields that are under validation. Just add a niceNames() class method returning an array where the keys are the field names and the values are their display names. Below is an example.


```php
protected function niceNames()
{
    return array(
        'email'     => 'email address'
    );
}
```

Now, anytime there are issues with email validation, the message to the user will say "email address" instead of "email". Optionally you could also make use of trans() or some custom logic code for returning localized or computed display names.

Note: in older versions this feature was implemented as the $nicenames class property. Legacy support for this is preserved but you are encouraged to use niceNames() in new models.

##Controller Example
Here is an example `store` method:

```php
/**
 * Store a newly created resource in storage.
 *
 * @return Response
 */
public function store()
{
  $s = User::create(Input::all());

  if($s->isSaved())
  {
    return Redirect::route('users.index')
      ->with('flash', 'The new user has been created');
  }

  return Redirect::route('users.create')
    ->withInput()
    ->withErrors($s->errors());
}
```
**First** Use Laravel's create method and send in the `Input::all()`. Save the return value into a variable.

**Second** Determine whether the model saved corrected using the `saved()` method.

**Third** Return the validation errors using the `errors()` method.

The returned errors use Laravel's [MessageBag](http://laravel.com/docs/validation#error-messages-and-views).

## License
The MIT License (MIT)

Copyright (c) 2014 Philip Brown and Alex Sears

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
