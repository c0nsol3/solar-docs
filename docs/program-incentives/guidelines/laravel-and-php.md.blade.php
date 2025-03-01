---
title: Laravel & PHP
---

# Laravel & PHP

<x-alert type="info">
**These guidelines are based on the [Laravel & PHP](https://spatie.be/guidelines/laravel-php) Guidelines by [Spatie](https://spatie.be/) as they provide a solid foundation for modern development.** Our guidelines contain slight modifications that are applicable to how we organize and develop projects in our day-to-day business operations.
</x-alert>

## Formatting

Use [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) to apply the styleguide guidelines to all files. This ensures consistent formatting across all projects. You can find the configuration in existing projects in the `.php_cs` file that should be the same across all projects.

## Classes

Use the `final` keyword for all classes. This ensures that new developers know what classes can be extended and which not. This will avoid unnecessary inheritance and encourage the use of traits.

Name classes in a way that makes it easy to figure out what they are doing before opening them in an editor, avoid highly technical naming. This ensures that searching for something is easier by using familiar terminology that can be guessed instead of having to know it beforehand. An example would be a controller that is named `ListPostsByUserController` instead of `UserPostsController`.

Both generally imply the same behaviors but the first is more clear by stating that it will list the posts of a given user and makes it possible to filter by `Lists` in your editor to find all controllers that list something instead of having to dig through folders that are full of controllers with generic names that are highly technical.

## Comments

Avoid the use of comments. Your code should be expressive enough to remove the need for further explanation and types should be hinted instead of using comments like `/** @var string */` because they create more noise than they add value.

If you think that a bit of code is so complex that it needs accompanying comments to explain what it does there is most likely a chance for a refactor. Ask another developer to take a look at the code and figure out if you could break down the functionality into multiple functions. A single class where each function has a name that clearly expresses what is happening is often much more expressive than spreading things across multiple classes or keeping them in simple functions outside of classes.

Maybe there altogether is a simpler implementation or an existing package which could be pulled in from [Packagist](https://packagist.org/) to solve the issue at hand and remove the need for the implementation and comments. Always make sure that you check if there already is an existing solution to your problem before you start to reinvent the wheel.

## Controllers

Always use [Single Action Controllers](https://laravel.com/docs/8.x/controllers#single-action-controllers), even when working on REST APIs, where traditionally, [Resource Controllers](https://laravel.com/docs/8.x/controllers#resource-controllers) would be used. Single Action Controllers have expressive names that tell more than a generic controller name with a handful of RESTful named methods. As a result of this tests will also become less noisy because you have separated each endpoint into its own controller that can now be easily tested without the noise of other endpoints in the same file.

Avoid extending the controller that ships with Laravel if you don't need any of the functionality that is provided by the traits that are included in it. If you do need the functionality from a specific trait you should include it directly in the controller that needs it. An exception to this rule is if the vast majority of your controllers need access to the traits that are provided by the controller.

This should rarely be the case because middlewares should be applied either in the `RouteServiceProvider` or inside the route files by using the `middleware` method for specific routes or `group(['middleware' => []])` method to apply the same middleware to multiple endpoints. Validation should be performed by calling `$request->validate()` or using by using [Form Request Validation](https://laravel.com/docs/8.x/validation#form-request-validation) to extract more complex rules and processing out of the controller.

## Models

Most Laravel models will extend the base Eloquent class. While there are a handful of syntactic sugars provided directly by Eloquent, it's important to note that these should be used with caution and that the database schema is extremely important, yet volatile component of a system. As a result, there are caveats to these syntactic sugars written below as well as their alternatives.

### Accessors

Eloquent provides a way to generate model attributes on-the-fly, using so called [model accessors](https://laravel.com/docs/8.x/eloquent-mutators#defining-an-accessor). Even though they provide a shortcut to dynamically generate attributes (such as "full name" generated from the "first name" and "last name" attributes), if you're not converting a model to a JSON form and generating an API response from the model (even then, there are alternatives such as API resources), there are certain trade-offs. One is poor IDE support, inability to use these dynamically generated attributes during ordering or querying the model, but the primary reason is hurting the readability of the code and inability to differentiate an actual database field from the dynamically generated attribute, without digging in the model class. In situations like these, always start by implementing a simple method to the model class to serve as a getter.

#### Good

```php
class User extends Model
{
    public function name()
    {
        return $this->first_name.' '.$this->last_name;
    }
}

// $user->name();
```

#### Bad

```php
class User extends Model
{
    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }
}

// $user->name;
```

So, what is the good use case for using accessors? Imagine a situation when working on an older codebase where you stored both user's first name and last name and also user's full name in the database. You make changes and want to remove the `full_name` from the database. To save yourself the hassle of updating all the references to the `full_name` attribute throughout the codebase and from a declined PR, consider creating the magic accessor to serve as a temporary proxy for this database column.

### Building `WHERE` clauses

Just like with accessors, Eloquent allows the developer to use a syntax sugar (dynamically building the `where()` method) to generate the `WHERE` clause while building the database query. An example is if you want to locate the user record by their name, you can chain the `->whereName('John Doe')` call to the query builder.

This should never be used and you should always define the column name appropriately as a first parameter in the `where()` method.

```php
// Bad ❌
User::whereName('John Doe')->first();

// Good ✅
User::where('name', 'John Doe')->first();
```

Explicitly defining column name as string parameters makes it more clear which column is targeted, especially when there's potential collision with common Laravel methods like `whereDate`, `whereColumn`, `whereRaw` and others. This produces clearer and more maintainable code, making future refactorings easier for fellow developers.

### Complex queries

If you see yourself reaching for [query scopes](https://laravel.com/docs/8.x/eloquent#query-scopes) a lot and have a lot of custom database queries in a model, consider building a custom query builder instance and attach it to the model.

```php
use Illuminate\Database\Eloquent\Builder;

class UserQuery extends Builder
{
    public function banned() : self
    {
        return $this->whereNotNull('banned_at');
    }

    // ...
}
```

```php
use App\Queries\UserQuery;

class User extends Model
{
    // ...

    public function newEloquentBuilder($query) : UserQuery
    {
        return new UserQuery($query);
    }
}

// User::banned()->first()
```

This will reduce complexity in the model class, provide a good IDE support and appropriately separate the concerns, where model class wouldn't need to contain dozens of scopes manipulating the query builder. Testing a custom query builder can be performed by simply unit testing the query builder instance, apart from the user model test.

## Database Migrations

You should always avoid the usage of the `down` method in migrations which can be used for database rollbacks. There are several reasons why you should avoid the usage of this method but the primary reason is the loss of data in production. It is quite risky to rollback a production database and more often than not you will lose data and have to apply a database backup which costs even more time.

The `down` methods also make it more difficult to follow the order in which things are applied and what exactly is happening because a single `add_post_id_to_images` migration technically could also be `drop_post_id_to_images` at the same time because it has an `up` and `down` method which add and drop the same column depending on how it is used. Using separate migrations for those tasks makes it more clear what is happening and the `down` method is dead code until you actually need it, which should rarely be the case.

You can listen to [https://www.laravelpodcast.com/episodes/5fc5650b](https://www.laravelpodcast.com/episodes/5fc5650b) for some more info on why the `down` method is a bad idea and shouldn't be used in production applications.

## Testing

Always use [Pest](https://pestphp.com/) for testing of PHP projects. It offers an an expressive API, comparable to [Jest](https://jestjs.io/), and massively reduces the amount of boilerplate that is needed to maintain our test suites. Pest is a relatively new tool that is gaining traction fast, which means you might encounter bugs, so submit a pull request with a fix if you do.

Following the spirit of Pest by keeping things as simple and as minimal as possible you should aim for the same with your tests. Keep them simple and to the point. Break larger tests down into smaller tests that each test specific behaviors instead of writing monolithic 100 line tests that are difficult to bisect and alter.

## Static Analysis

Static Analysis is your best friend when it comes to writing maintainable code. Tools like [PHPStan](https://github.com/phpstan/phpstan), [Larastan](https://github.com/nunomaduro/larastan), [Pslam](https://psalm.dev/) and [Rector](https://github.com/rectorphp/rector) will help you discover bugs, inconsistent code or even missing classes and functions before you even run your application.

These tools should always be used with their most strict configurations. This ensures that the code stays maintainable and makes use of the latest PHP features and best practices for the version that we are currently using. For older projects the introduction of those tools will mean a migration over time to resolve all of the issues that get reported. Every developer should resolve a few of the issues reported by those tools when he has a few minutes time for it.

Do keep in mind that some issues might be false-positives because things like [Facades](https://laravel.com/docs/8.x/facades) require additional type hints and comments in your code so that these tools can understand what the underlying binding of a facade is doing. A few of the aforementioned tools have plugins for Laravel that try to solve this issue but you might still occasionally end up with false-positives.

## Usage of Composer dependencies

When building features, there will be a dilemma whether to pull in a Composer dependency that performs the needed action or to build your own from scratch. Try to base the decision on several things, such as **complexity of the feature, tests and the technical debt**. In general, if the dependency hasn't received an update in a long time, isn't actively maintained or has little to no tests -- always prefer to build your own. Pulling in the library that has very little tests can be a maintenance burden in the future. Because we try to keep high standards regarding static analysis in SXP projects, third-party libraries often do not satisfy these static analysis tools, so think about that as well.

Developing from scratch gives you more control and allows you to fine-tune the feature to your own specific needs, and of course make you learn something new. On the other hand, reinventing the wheel can cause you to waste time. Always weigh the advantages and disadvantages before making a final decision. Of course, there are several instances where you would never try to develop your own features, but rather use huge libraries which are actively maintained and would save a lot of time. A few examples are if you need to interact with the Stripe API, or the AWS SDK -- you would never try to build your own, but rather pull in their respective libraries.

Sometimes, building from scratch results in a more maintainable code than pulling in a third-party library.

## Front-End Interactions

If you need interactivity for certain functionality or want to avoid page reloads you should use [Laravel Livewire](https://laravel-livewire.com/) and [Alpine.js](https://github.com/alpinejs/alpine). Livewire allows you to seamlessly integrate with the back-end without having to build a separate API for communication with the back-end. Alpine provides you with an expressive API to manipulate the DOM without having to pull in heavyweights like [Vue](https://vuejs.org/) or [React](https://reactjs.org/).

If you find yourself in a situation where you think Livewire or Alpine are insufficient you should make your case for why you think it is necessary to use a different solution or framework for the task at hand.

## Livewire

### Data binding

There are 2 common ways to [bind data](https://laravel-livewire.com/docs/2.x/properties#data-binding) from a client-side to the Livewire component in the back-end using `wire:model` directive -- real-time and deferred.

Use real-time data binding (`wire:model="state.name"`) when you need the real-time validation and when you need certain aspects changed in real-time ("as user types"). Keep in mind that Livewire will send an HTTP request to the back-end every time an input event is emitted on the HTML element (typed into input field, etc). When opting for real-time data binding, consider modifying the debounce to fit the needs of the component.

Use deferred updating (`wire:model.defer="state.name"`) if the component doesn't need real-time validation and there's no need to update the UI in real time. If there's no need to perform an AJAX request to the back-end to store and validate the input, there's no reason to use real-time data binding. Deferred updating can drastically reduce the number of requests.

In general, always prefer deferred updating to save the network usage and improve performance, but fallback to real-time when you need it.

### Rendering

Livewire can automatically determine the view that should be used. This means that you should generally omit the `render` method and let Livewire figure out what should be rendered. This means less code to think about and no room for human error in referencing a wrong view.

If you are working on a project that makes use of DDD you will either have to keep the render method because of the way that Livewire resolves the view location or overwrite the method that resolves the view location to only take the class name into account instead of the FQCN of the component. Overriding the resolution method is recommended if a large number of components are used.

#### Blade component as an alternative

While Livewire allows developers to quickly build interactive UIs, always using Livewire for UI components can be a bad practice -- Blade components combined with [Alpine.js](https://github.com/alpinejs/alpine) can also produce rich UI elements. If the component requires no interactivity (simply rendering an HTML), always prefer Blade before a Livewire component. An example is when a root Livewire component iterates over specific items and renders each of them as a UI component. If no item in the list requires any interactivity with the server-side then always prefer to componentize using Blade components.

Only using Livewire when it's needed lowers the performance overhead created by Livewire's boot time. Because Livewire has both front-end and back-end components, not using Livewire for every UI component can significantly improve the performance of the application. Other than performance overhead, this can help lessen the amount of [DOM diffing issues](https://laravel-livewire.com/docs/2.x/troubleshooting) and bugs created by nesting Livewire components.

### State Management

When working with Livewire you will work a lot with models and their array representations or small bits of data from them that you will need to update. All of this data should be looked at as the state of the component and be stored in a `$state` array that will hold all of the values that are modified on the component. An exception to this rule are models that are passed to the `mount` method because these models will not be modified directly, they only are modified through updates that are using the state data.

#### Good

```php
namespace App\Http\Livewire;

use Livewire\Component;

class UpdateUserNameForm extends Component
{
    public User $user;
    public array $state = [];

    public function mount(User $user)
    {
        $this->user  = $user;
        $this->state = ['name' => $user->name];
    }
}
```

#### Bad

```php
namespace App\Http\Livewire;

use Livewire\Component;

class UpdateUserNameForm extends Component
{
    public User $user;
    public string $name;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
    }
}
```

#### What counts as "state"

If the Livewire component contains forms, all the input fields within the form will count as state, and always keep those in the `$state` property. Storing these fields as a separate property would result in potentially dozens of properties within the component and would produce a mess. In that case, it would be hard to visually differentiate which properties are form state and which properties are relevant component data.

If you're displaying a loading indicator, interacting with Eloquent models or resolving services, these can be safely stored as an additional property on the component.

```php
namespace App\Http\Livewire;

use Livewire\Component;

class UpdateUserForm extends Component
{
    public bool $isLoading = false;
    protected Stripe $stripe;

    public array $state = [
        'name' => null,
        'email' => null,
    ];
}
```

### Validation

Always ensure to apply validation before performing an action that makes use of component state. This validation can be either performed in real-time or at the time of a method call. If a method is being executed based on events or instant feedback is executed it is recommended to use real-time validation for faster feedback for an improved UX.

### Authorization

If the component performs database queries to retrieve models, always make sure the user performing the request is authorized to retrieve the model. A good example is if you're locating a server model, but the constraint is that the server instance is tied to the user instance (through a one-to-many relationship), always retrieve the server model via the relationship. This prevents bugs where unauthorized users can perform illegal actions.

On top of that, always ensure user has appropriate permissions to interact with the model, using policies, permissions or gates (depending on the application setup). To help you with that, Livewire offers you to import the `Illuminate\Foundation\Auth\Access\AuthorizesRequests` trait into the component and will properly handle all unauthorized responses. To read more, check out the [Livewire documentation](https://laravel-livewire.com/docs/2.x/authorization) on this topic.

```php
namespace App\Http\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class DeleteServer extends Component
{
    use InteractsWithUser, AuthorizesRequests;

    public function submit($serverId)
    {
        // Bad ❌
        $server = Server::findOrFail($serverId);

        // Good ✅
        $server = $this->user->servers()->findOrFail($serverId);

        // Authorize against deletion...
        $this->authorize('delete', $server);
    }
}
```

### Localization

For localization, we make use of `kebab-case` for keys that contain nested entries and `snake_case` for key value pairs. As for parameters, we use `camelCase`

Use `@ lang()`, `trans()` and `__()` in that order of priority when possible.

#### Single Key

```php
return [
    // Good
    'foo'     => 'bar'
    'foo_bar' => 'baz'
    // Bad
    'foo-bar' => 'baz'
]
```

#### Nested Entry

```php
return [
    // Good
    'foo' => [
        'bar'     => 'baz',
        'foo_bar' => 'buz'
    ]

    // Bad
    'foo' => [
        'bar'     => 'baz',
        'foo-bar' => 'buz'
    ]

    // Good
    'foo-bar' => [
        'bar'     => 'baz',
        'foo_bar' => 'buz'
    ]

    // Bad
    'foo_bar' => [
        'bar'     => 'baz',
        'foo_bar' => 'buz'
    ]
]
```

#### Parameters

```php
return [
    // Good
    'foo'     => 'bar :bazFoo'
    'foo_bar' => 'baz :bazFoo'
    // Bad
    'foo'     => 'baz :baz_foo'
    'foo-bar' => 'baz :baz-foo'
]
```
