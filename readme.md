# EloquentQL

This library helps to negotiate content related to eloquent models (fields, relations and filters)

## Installation

Install the package using composer:

    $ composer require eloquentQl ~1.0

Publish the package configuration:
	$ php artisan vendor:publish --provider="Sympla\Search\Search\SearchServiceProvider"

That's it.

## Simple usage


```php
public function index(Request $request)
{
    $res = $res->negotiate('Models\User');
    return response()->json($res);
}
```

Create your filter 

```php
public function scopeFilterByPhone($query)
{
   return $query->where('phone', '<>', '');
}
```

Now you simply call your route with your filter and the fields you want to return in the request

```
http://localhost:8000/api/users?&fields=name,email&filters=filterByPhone
```

## Using with Laravel

### Service Provider (Optional on Laravel 5.5)
Once Composer has installed or updated your packages you need add aliases or register you packages into Laravel. Open up config/app.php and find the aliases key and add:

```
Sympla\Search\Search\SearchServiceProvider::class,
```

## Contact

Bruno Coelho <bruno.coelho@sympla.com.br>

## License

This project is distributed under the MIT License. Check [LICENSE][LICENSE.md] for more information.
