# brunaobh/eloquent-ql

This library helps to negotiate content related to eloquent models (fields, relations and filters)

## Installation

Install the package using composer:

    $ composer require brunaobh/eloquent-ql

Publish the package configuration:
	$ php artisan vendor:publish --provider="brunaobh\Search\Search\SearchServiceProvider"

That's it.

## Simple usage


```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Search;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $res = Search::handleRequest($request)
            ->negotiate('User')
            ->get();
        
        return response()->json($res);
    }
```

Create your filter 

```php
public function scopeFilterByAddressNotNull($query)
{
   return $query->whereNotNull('address');
}
```

Now you simply call your route with your filter and the fields you want to return in the request

```
http://localhost:8000/api/users?fields=name,email&filters=filterByAddressNotNull
```

## Using with Laravel

### Service Provider (Optional on Laravel 5.5)
Once Composer has installed or updated your packages you need add aliases or register you packages into Laravel. Open up config/app.php and find the aliases key and add:

Providers:
```
brunaobh\Search\Search\SearchServiceProvider::class,
```

Aliases:
```
brunaobh\Search\Search\SearchServiceProvider::class,

'Search' => brunaobh\Search\Facades\Search::class,
```


## Contact

Bruno Coelho <brunaobh@gmail.com>

## License

This project is distributed under the MIT License. Check [LICENSE][LICENSE.md] for more information.
