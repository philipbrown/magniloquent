Magniloquent
============

Self-validating models for Laravel 4's Eloquent.

Based on the excellent [Ardent](https://github.com/laravelbook/ardent) package by [Max Ehsan](https://github.com/laravelbook).

This package is highly inspired by Ardent. I wanted to make some big changes and so I thought it would be better to start a new package rather than fundamentally change how Ardent validates data. If you are looking to extend Eloquent's functionality, you should also check out Ardent!

Magniloquent was extracted from [Cribbb](https://github.com/cribbb/cribbb).

##Installation
Add `magniloquent/magniloquent` as a requirement to `composer.json`:

```javascript
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
    'email' => 'required|email',
    'password' => 'required|min:8'
  ),
  "create" => array(
    'username' => 'unique:users',
    'email' => 'unique:users',
    'password' => 'confirmed',
    'password_confirmation' => 'required|min:8'
  ),
  "update" => array()
);
```
The `save` array are validation rules that are applicable whenever the model is changed. The `create` and `update` arrays are only added on their respective methods.

So in the example above, when a user is created, the username should be unique. When the user updates any of their information, the uniqueness validation test won't be applied.

##Controller example
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

  if($s->saved())
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
**Third** Return the validation errors using the `error()` method.

The returned errors use Laravel's [MessageBag](http://laravel.com/docs/validation#error-messages-and-views).
