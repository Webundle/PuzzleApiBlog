# Puzzle API Blog Bundle
**=========================**

Puzzle Blog API

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

`composer require webundle/puzzle-api-blog`

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
{
    $bundles = array(
    // ...

    new Puzzle\Api\BlogBundle\PuzzleApiBlogBundle(),
                    );

 // ...
}

 // ...
}
```

### Step 3: Define host apis parameter

Define host apis parameter if it is not yet (usually in the `app/config/parameters.yml` file):

# app/config/parameters.yml
```yaml
parameters:
	...
   	host_apis: '<host_apis_uri>'
```

### Step 4: Register the Routes

Load the bundle's routing definition in the application (usually in the `app/config/routing.yml` file):

# app/config/routing.yml
```yaml
puzzle_api_blog:
    resource: "@PuzzleApiBlogBundle/Resources/config/routing.yml"
    prefix:   /v1/blog
    host: '%host_apis%'
```

### Step 4: Enable services

Load the bundle's routing definition in the application (usually in the `app/config/config.yml` file):

# app/config/config.yml
```yaml
imports:
    ...
    - { resource: '@PuzzleApiBlogBundle/Resources/config/services.yml' }
```